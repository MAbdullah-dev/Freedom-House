<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CalendarEvent;
use App\Models\CheckoutSnapshot;
use App\Models\Classe;
use App\Models\Course;
use App\Models\Events;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\StripeEvent;
use App\Models\StripeRefundNotice;
use App\Models\User;
use App\Services\Microsoft\GraphSyncProcessor;
use App\Services\Stripe\StripeGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnrollmentFulfillment
{
    public const FULFILLED = 'fulfilled';

    public const UNFULFILLABLE = 'unfulfillable';

    public const REJECTED = 'rejected';

    public const REFUNDED = 'refunded';

    public const PARTIALLY_REFUNDED = 'partially_refunded';

    public function fulfillFromSession(array $session): FulfillmentResult
    {
        $normalized = $this->normalizeSession($session);

        try {
            $result = DB::transaction(fn () => $this->fulfillInside($normalized), 3);
        } catch (UniqueConstraintViolationException) {
            return $this->recordDuplicateAfterConflict($normalized);
        }

        if ($result->newlyGranted && $result->booking !== null) {
            $this->afterAccessGranted($result->booking);
        }

        return $result;
    }

    private function recordDuplicateAfterConflict(array $session): FulfillmentResult
    {
        $existing = Payment::query()->where('stripe_session_id', $session['id'])->first();
        if ($existing !== null) {
            return new FulfillmentResult($this->outcomeForPayment($existing), $existing, $existing->booking);
        }

        $snapshot = CheckoutSnapshot::query()->where('stripe_session_id', $session['id'])->first();
        if ($snapshot === null || $session['payment_status'] !== 'paid') {
            return new FulfillmentResult(self::REJECTED, reason: 'conflict');
        }

        $payment = Payment::query()->create([
            'amount' => number_format(((int) $session['amount_total']) / 100, 2, '.', ''),
            'booking_id' => null,
            'payment_method' => 'stripe',
            'transaction_id' => $session['id'],
            'checkout_snapshot_id' => $snapshot->id,
            'stripe_session_id' => $session['id'],
            'stripe_payment_intent_id' => $session['payment_intent'],
            'amount_cents' => (int) $session['amount_total'],
            'currency' => $session['currency'] !== '' ? $session['currency'] : null,
            'refunded_cents' => 0,
            'user_id' => $snapshot->user_id,
            'status' => Payment::STATUS_UNFULFILLABLE,
            'resolution_required' => true,
            'resolution_reason' => 'duplicate_enrollment',
        ]);
        $snapshot->state = CheckoutSnapshot::UNFULFILLABLE;
        $snapshot->save();

        return new FulfillmentResult(self::UNFULFILLABLE, $payment, reason: 'duplicate_enrollment');
    }

    public function handleEvent(string $eventId, string $type, array $object): void
    {
        if (StripeEvent::query()->where('event_id', $eventId)->exists()) {
            return;
        }

        match ($type) {
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' => $this->fulfillFromSession($object),
            'checkout.session.async_payment_failed' => $this->markUnsuccessful($object, CheckoutSnapshot::FAILED),
            'checkout.session.expired' => $this->markUnsuccessful($object, CheckoutSnapshot::EXPIRED),
            'charge.refunded' => $this->applyChargeRefund($object),
            default => null,
        };

        try {
            StripeEvent::query()->create([
                'event_id' => $eventId,
                'type' => $type,
            ]);
        } catch (UniqueConstraintViolationException) {
            // A concurrent delivery recorded the same event after the work was applied.
        }
    }

    public function retrieveForUser(string $sessionId, User $user): FulfillmentResult
    {
        $session = app(StripeGateway::class)->retrieveCheckoutSession($sessionId);
        $normalized = $this->normalizeSession($session);

        if (($normalized['metadata']['user_id'] ?? '') !== (string) $user->id) {
            return new FulfillmentResult(self::REJECTED, reason: 'wrong_user');
        }

        return $this->fulfillFromSession($normalized);
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    public function normalizeSession(array $session): array
    {
        $metadata = $session['metadata'] ?? [];
        if (is_object($metadata) && method_exists($metadata, 'toArray')) {
            $metadata = $metadata->toArray();
        }
        $intent = $session['payment_intent'] ?? null;
        if (is_array($intent)) {
            $intent = $intent['id'] ?? null;
        }

        return [
            'id' => isset($session['id']) ? (string) $session['id'] : '',
            'payment_status' => isset($session['payment_status']) ? (string) $session['payment_status'] : '',
            'status' => isset($session['status']) ? (string) $session['status'] : '',
            'amount_total' => is_numeric($session['amount_total'] ?? null) ? (int) $session['amount_total'] : null,
            'currency' => strtolower((string) ($session['currency'] ?? '')),
            'payment_intent' => is_string($intent) ? $intent : null,
            'metadata' => [
                'user_id' => (string) ($metadata['user_id'] ?? ''),
                'item_type' => (string) ($metadata['item_type'] ?? ''),
                'item_id' => (string) ($metadata['item_id'] ?? ''),
                'snapshot_id' => (string) ($metadata['snapshot_id'] ?? ''),
            ],
        ];
    }

    private function fulfillInside(array $session): FulfillmentResult
    {
        if ($session['id'] === '') {
            return new FulfillmentResult(self::REJECTED, reason: 'missing_session');
        }

        $snapshot = CheckoutSnapshot::query()
            ->where('stripe_session_id', $session['id'])
            ->lockForUpdate()
            ->first();

        if ($snapshot === null && is_numeric($session['metadata']['snapshot_id'] ?? null)) {
            $candidate = CheckoutSnapshot::query()
                ->lockForUpdate()
                ->find((int) $session['metadata']['snapshot_id']);
            if ($candidate !== null && ($candidate->stripe_session_id === null || $candidate->stripe_session_id === $session['id'])) {
                $snapshot = $candidate;
            }
        }

        if ($snapshot === null) {
            return new FulfillmentResult(self::REJECTED, reason: 'unknown_session');
        }

        $existing = $this->lockedPaymentForSession($session['id']);
        if ($existing !== null) {
            return new FulfillmentResult($this->outcomeForPayment($existing), $existing, $existing->booking);
        }

        if (! $this->metadataMatches($snapshot, $session)) {
            return $this->rejectMismatchedMetadata($snapshot, $session);
        }

        if ($session['payment_intent']) {
            $snapshot->stripe_payment_intent_id = $session['payment_intent'];
        }
        if ($snapshot->stripe_session_id === null) {
            $snapshot->stripe_session_id = $session['id'];
        }

        if ($session['payment_status'] !== 'paid') {
            if ($session['status'] === 'expired') {
                $snapshot->state = CheckoutSnapshot::EXPIRED;
                $snapshot->save();
            } else {
                $snapshot->save();
            }

            return new FulfillmentResult(self::REJECTED, reason: 'unpaid');
        }

        $item = $this->lockItem($snapshot);
        $existing = $this->lockedPaymentForSession($session['id']);
        if ($existing !== null) {
            return new FulfillmentResult($this->outcomeForPayment($existing), $existing, $existing->booking);
        }

        $notice = $session['payment_intent']
            ? StripeRefundNotice::query()->lockForUpdate()->where('payment_intent_id', $session['payment_intent'])->first()
            : null;

        $amount = $session['amount_total'];
        $currency = $session['currency'];
        $amountMatches = $amount === (int) $snapshot->amount_cents;
        $currencyMatches = $currency === $snapshot->currency;
        $fullyRefunded = $notice?->fully_refunded === true;

        $reason = null;
        $grant = $amountMatches && $currencyMatches && ! $fullyRefunded;
        if (! $currencyMatches) {
            $reason = 'currency_mismatch';
        } elseif (! $amountMatches) {
            $reason = 'amount_mismatch';
        } elseif ($fullyRefunded) {
            $reason = 'refunded_before_completion';
        }

        $payment = new Payment([
            'amount' => number_format(((int) $amount) / 100, 2, '.', ''),
            'booking_id' => null,
            'payment_method' => 'stripe',
            'transaction_id' => $session['id'],
            'checkout_snapshot_id' => $snapshot->id,
            'stripe_session_id' => $session['id'],
            'stripe_payment_intent_id' => $session['payment_intent'],
            'amount_cents' => (int) $amount,
            'currency' => $currency !== '' ? $currency : null,
            'refunded_cents' => $fullyRefunded ? (int) $amount : (int) ($notice->amount_refunded ?? 0),
            'user_id' => $snapshot->user_id,
        ]);

        if (! $grant) {
            $payment->status = $fullyRefunded ? Payment::STATUS_REFUNDED : Payment::STATUS_UNFULFILLABLE;
            $payment->resolution_required = ! $fullyRefunded;
            $payment->resolution_reason = $reason;
            $payment->save();
            $snapshot->state = $fullyRefunded ? CheckoutSnapshot::REFUNDED : CheckoutSnapshot::UNFULFILLABLE;
            $snapshot->save();

            return new FulfillmentResult(
                $fullyRefunded ? self::REFUNDED : self::UNFULFILLABLE,
                $payment,
                reason: $reason
            );
        }

        if ($item === null) {
            $payment->status = Payment::STATUS_UNFULFILLABLE;
            $payment->resolution_required = true;
            $payment->resolution_reason = 'missing_item';
            $payment->save();
            $snapshot->state = CheckoutSnapshot::UNFULFILLABLE;
            $snapshot->save();

            return new FulfillmentResult(self::UNFULFILLABLE, $payment, reason: 'missing_item');
        }

        $key = $this->keyForSnapshot($snapshot);
        $active = $key === null
            ? null
            : Booking::query()->where('active_enrollment_key', $key)->lockForUpdate()->first();

        if ($key === null || $active !== null || $this->capacityExhausted($item)) {
            $reason = $active !== null || $key === null ? 'duplicate_enrollment' : 'capacity_exhausted';
            $payment->status = Payment::STATUS_UNFULFILLABLE;
            $payment->resolution_required = true;
            $payment->resolution_reason = $reason;
            $payment->save();
            $snapshot->state = CheckoutSnapshot::UNFULFILLABLE;
            $snapshot->save();

            return new FulfillmentResult(self::UNFULFILLABLE, $payment, reason: $reason);
        }

        try {
            $booking = Booking::query()->create([
                'user_id' => $snapshot->user_id,
                'course_id' => $snapshot->item_type === 'course' ? $snapshot->item_id : null,
                'class_id' => $snapshot->item_type === 'class' ? $snapshot->item_id : null,
                'event_id' => $snapshot->item_type === 'event' ? $snapshot->item_id : null,
                'payment_status' => 'paid',
                'type' => 'automated',
                'order_number' => uniqid('PAID_', true),
                'phone' => User::query()->whereKey($snapshot->user_id)->value('phone'),
            ]);
        } catch (UniqueConstraintViolationException) {
            $payment->status = Payment::STATUS_UNFULFILLABLE;
            $payment->booking_id = null;
            $payment->resolution_required = true;
            $payment->resolution_reason = 'duplicate_enrollment';
            $payment->save();
            $snapshot->state = CheckoutSnapshot::UNFULFILLABLE;
            $snapshot->save();

            return new FulfillmentResult(self::UNFULFILLABLE, $payment, reason: 'duplicate_enrollment');
        }

        if (! $this->takeSeat($item)) {
            $booking->delete();
            $payment->status = Payment::STATUS_UNFULFILLABLE;
            $payment->resolution_required = true;
            $payment->resolution_reason = 'capacity_exhausted';
            $payment->save();
            $snapshot->state = CheckoutSnapshot::UNFULFILLABLE;
            $snapshot->save();

            return new FulfillmentResult(self::UNFULFILLABLE, $payment, reason: 'capacity_exhausted');
        }

        if ($snapshot->item_type !== 'course') {
            $column = $snapshot->item_type === 'class' ? 'class_id' : 'event_id';
            CalendarEvent::query()->create([
                'user_id' => $snapshot->user_id,
                $column => $snapshot->item_id,
            ]);
        }

        $partial = $notice !== null && (int) $notice->amount_refunded > 0;
        $payment->booking_id = $booking->id;
        $payment->status = $partial ? Payment::STATUS_PARTIALLY_REFUNDED : Payment::STATUS_PAID;
        $payment->resolution_required = false;
        $payment->resolution_reason = null;
        $payment->save();

        $snapshot->state = $partial ? CheckoutSnapshot::PARTIALLY_REFUNDED : CheckoutSnapshot::FULFILLED;
        $snapshot->save();

        return new FulfillmentResult(
            $partial ? self::PARTIALLY_REFUNDED : self::FULFILLED,
            $payment,
            $booking,
            newlyGranted: true
        );
    }

    public function markUnsuccessful(array $session, string $state): void
    {
        $sessionId = $session['id'] ?? null;
        if (! is_string($sessionId) || $sessionId === '') {
            return;
        }

        DB::transaction(function () use ($sessionId, $state) {
            $snapshot = CheckoutSnapshot::query()
                ->where('stripe_session_id', $sessionId)
                ->lockForUpdate()
                ->first();

            if ($snapshot === null || Payment::query()->where('stripe_session_id', $sessionId)->exists()) {
                return;
            }

            if (in_array($snapshot->state, [
                CheckoutSnapshot::FULFILLED,
                CheckoutSnapshot::UNFULFILLABLE,
                CheckoutSnapshot::REFUNDED,
                CheckoutSnapshot::PARTIALLY_REFUNDED,
            ], true)) {
                return;
            }

            $snapshot->state = $state;
            $snapshot->save();
        });
    }

    public function applyChargeRefund(array $charge): void
    {
        $intent = $charge['payment_intent'] ?? null;
        if (is_array($intent)) {
            $intent = $intent['id'] ?? null;
        }
        if (! is_string($intent) || $intent === '') {
            return;
        }

        $amount = (int) ($charge['amount'] ?? 0);
        $refunded = (int) ($charge['amount_refunded'] ?? 0);
        $full = ($charge['refunded'] ?? false) === true || ($amount > 0 && $refunded >= $amount);

        $this->applyRefund($intent, $refunded, strtolower((string) ($charge['currency'] ?? '')), $full);
    }

    public function applyRefund(string $paymentIntentId, int $amountRefunded, string $currency, bool $chargeFullyRefunded): void
    {
        DB::transaction(function () use ($paymentIntentId, $amountRefunded, $currency, $chargeFullyRefunded) {
            $existing = Payment::query()->where('stripe_payment_intent_id', $paymentIntentId)->first();
            if ($existing?->checkout_snapshot_id) {
                CheckoutSnapshot::query()->lockForUpdate()->find($existing->checkout_snapshot_id);
            }

            $payment = $existing === null
                ? null
                : Payment::query()->whereKey($existing->id)->lockForUpdate()->first();

            $notice = StripeRefundNotice::query()
                ->lockForUpdate()
                ->firstOrNew(['payment_intent_id' => $paymentIntentId]);
            $notice->amount_refunded = max((int) $notice->amount_refunded, $amountRefunded);
            if ($payment !== null && $chargeFullyRefunded) {
                $notice->amount_refunded = max($notice->amount_refunded, (int) $payment->amount_cents);
            }
            $notice->currency = $currency !== '' ? $currency : $notice->currency;
            $notice->fully_refunded = $chargeFullyRefunded
                || ($payment !== null
                    && (int) $payment->amount_cents > 0
                    && $notice->amount_refunded >= (int) $payment->amount_cents);
            $notice->save();

            if ($payment === null) {
                return;
            }

            $full = (bool) $notice->fully_refunded;
            $payment->refunded_cents = $notice->amount_refunded;

            if (! $full) {
                $payment->status = Payment::STATUS_PARTIALLY_REFUNDED;
                $payment->save();
                $this->markSnapshot($payment, CheckoutSnapshot::PARTIALLY_REFUNDED);

                return;
            }

            $payment->status = Payment::STATUS_REFUNDED;
            if ($payment->booking_id) {
                $booking = Booking::query()->lockForUpdate()->find($payment->booking_id);
                if ($booking !== null && in_array($booking->payment_status, Booking::ACCESS_STATUSES, true)) {
                    $booking->payment_status = 'refunded';
                    $booking->save();
                    if ($booking->class_id || $booking->event_id) {
                        $payment->resolution_required = true;
                        $payment->resolution_reason = 'seat_not_released';
                    }
                    CalendarEvent::query()
                        ->where('user_id', $booking->user_id)
                        ->when($booking->class_id, fn ($query) => $query->where('class_id', $booking->class_id))
                        ->when($booking->event_id, fn ($query) => $query->where('event_id', $booking->event_id))
                        ->delete();
                }
            }
            $payment->save();
            $this->markSnapshot($payment, CheckoutSnapshot::REFUNDED);
        });
    }

    private function rejectMismatchedMetadata(CheckoutSnapshot $snapshot, array $session): FulfillmentResult
    {
        $ownsSession = $snapshot->stripe_session_id === $session['id'];

        if (! $ownsSession || $session['payment_status'] !== 'paid') {
            return new FulfillmentResult(self::REJECTED, reason: 'metadata_mismatch');
        }

        $amount = (int) ($session['amount_total'] ?? 0);
        $currency = $session['currency'];
        $payment = new Payment([
            'amount' => number_format($amount / 100, 2, '.', ''),
            'booking_id' => null,
            'payment_method' => 'stripe',
            'transaction_id' => $session['id'],
            'checkout_snapshot_id' => $snapshot->id,
            'stripe_session_id' => $session['id'],
            'stripe_payment_intent_id' => $session['payment_intent'],
            'amount_cents' => $amount,
            'currency' => $currency !== '' ? $currency : null,
            'refunded_cents' => 0,
            'user_id' => $snapshot->user_id,
            'status' => Payment::STATUS_UNFULFILLABLE,
            'resolution_required' => true,
            'resolution_reason' => 'metadata_mismatch',
        ]);
        $payment->save();
        $snapshot->state = CheckoutSnapshot::UNFULFILLABLE;
        $snapshot->save();

        return new FulfillmentResult(self::UNFULFILLABLE, $payment, reason: 'metadata_mismatch');
    }

    private function lockedPaymentForSession(string $sessionId): ?Payment
    {
        $existing = Payment::query()->where('stripe_session_id', $sessionId)->first();
        if ($existing === null) {
            return null;
        }

        return Payment::query()->whereKey($existing->id)->lockForUpdate()->first();
    }

    private function metadataMatches(CheckoutSnapshot $snapshot, array $session): bool
    {
        $meta = $session['metadata'];

        return $meta['user_id'] === (string) $snapshot->user_id
            && $meta['item_type'] === (string) $snapshot->item_type
            && $meta['item_id'] === (string) $snapshot->item_id
            && $meta['snapshot_id'] === (string) $snapshot->id;
    }

    private function lockItem(CheckoutSnapshot $snapshot): ?Model
    {
        return match ($snapshot->item_type) {
            'class' => Classe::query()->lockForUpdate()->find($snapshot->item_id),
            'course' => Course::query()->lockForUpdate()->find($snapshot->item_id),
            'event' => Events::query()->lockForUpdate()->find($snapshot->item_id),
            default => null,
        };
    }

    private function keyForSnapshot(CheckoutSnapshot $snapshot): ?string
    {
        return Booking::enrollmentKey(
            $snapshot->user_id,
            $snapshot->item_type === 'course' ? $snapshot->item_id : null,
            $snapshot->item_type === 'class' ? $snapshot->item_id : null,
            $snapshot->item_type === 'event' ? $snapshot->item_id : null,
        );
    }

    private function capacityExhausted(Model $item): bool
    {
        if (! $item instanceof Classe && ! $item instanceof Events) {
            return false;
        }

        return $item->capacity !== null && (int) $item->capacity <= 0;
    }

    private function takeSeat(Model $item): bool
    {
        if (! $item instanceof Classe && ! $item instanceof Events) {
            return true;
        }

        if ($item->capacity === null) {
            return true;
        }

        return DB::table($item->getTable())
            ->where('id', $item->id)
            ->where('capacity', '>', 0)
            ->decrement('capacity') === 1;
    }

    private function markSnapshot(Payment $payment, string $state): void
    {
        if ($payment->checkout_snapshot_id === null) {
            return;
        }

        $snapshot = CheckoutSnapshot::query()->lockForUpdate()->find($payment->checkout_snapshot_id);
        if ($snapshot === null) {
            return;
        }

        $snapshot->state = $state;
        $snapshot->save();
    }

    private function outcomeForPayment(Payment $payment): string
    {
        return match ($payment->status) {
            Payment::STATUS_PAID => self::FULFILLED,
            Payment::STATUS_PARTIALLY_REFUNDED => self::PARTIALLY_REFUNDED,
            Payment::STATUS_REFUNDED => self::REFUNDED,
            Payment::STATUS_UNFULFILLABLE => self::UNFULFILLABLE,
            default => self::REJECTED,
        };
    }

    private function afterAccessGranted(Booking $booking): void
    {
        $user = User::query()->find($booking->user_id);
        if ($user === null) {
            return;
        }

        try {
            Notification::query()->create([
                'user_id' => $user->id,
                'message' => 'Your payment was verified and your enrollment is active.',
                'type' => 'booking',
            ]);
        } catch (Throwable $exception) {
            Log::error('Enrollment notification failed.', ['exception' => $exception::class]);
        }

        $this->syncMicrosoft($user, $booking);
    }

    private function syncMicrosoft(User $user, Booking $booking): void
    {
        $item = $booking->class_id
            ? Classe::query()->find($booking->class_id)
            : ($booking->event_id ? Events::query()->find($booking->event_id) : null);

        if ($item === null || (! $user->microsoft_account && empty($item->microsoft_event_id))) {
            return;
        }

        try {
            app(GraphSyncProcessor::class)->enqueueAttendee($user, $booking);
        } catch (Throwable $exception) {
            Log::error('Paid enrollment calendar sync failed.', ['exception' => $exception::class]);
        }
    }
}
