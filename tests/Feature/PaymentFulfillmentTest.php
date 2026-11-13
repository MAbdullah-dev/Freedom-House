<?php

namespace Tests\Feature;

use App\Livewire\CourseDetails;
use App\Models\Booking;
use App\Models\CheckoutSnapshot;
use App\Models\Classe;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Role;
use App\Models\StripeEvent;
use App\Models\StripeRefundNotice;
use App\Models\User;
use App\Services\ContentAccess;
use App\Services\EnrollmentFulfillment;
use App\Services\Stripe\StripeGateway;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PaymentFulfillmentTest extends TestCase
{
    use DatabaseTransactions;

    private const SECRET = 'whsec_test_fulfillment';

    private FakeStripeGateway $stripe;

    private User $learner;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'services.stripe.webhook_secret' => self::SECRET,
            'services.stripe.currency' => 'usd',
            'services.stripe.secret' => 'sk_test_unused',
        ]);

        foreach ([Role::ADMIN, Role::TUTOR, Role::LEARNER] as $roleName) {
            Role::query()->firstOrCreate(['role_name' => $roleName]);
        }

        $this->owner = $this->makeUser('owner-pay@example.test', Role::TUTOR);
        $this->learner = $this->makeUser('learner-pay@example.test', Role::LEARNER);
        $this->stripe = new FakeStripeGateway;
        $this->app->instance(StripeGateway::class, $this->stripe);
    }

    public function test_a_fake_success_url_does_not_enroll(): void
    {
        $class = $this->makeClass(40, 5);

        $this->actingAs($this->learner)
            ->get(route('payment.success', [
                'session_id' => 'cs_fake',
                'class_id' => $class->id,
                'course_id' => $class->course_id,
            ]))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, Booking::query()->where('class_id', $class->id)->count());
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_an_unpaid_checkout_session_does_not_enroll(): void
    {
        [$class, $session] = $this->paidSession(40, 5);
        $session['payment_status'] = 'unpaid';
        $session['status'] = 'complete';

        $result = app(EnrollmentFulfillment::class)->fulfillFromSession($session);

        $this->assertSame(EnrollmentFulfillment::REJECTED, $result->outcome);
        $this->assertSame(0, Booking::query()->where('class_id', $class->id)->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(CheckoutSnapshot::OPEN, CheckoutSnapshot::query()->where('stripe_session_id', $session['id'])->value('state'));
    }

    public function test_a_paid_verified_session_enrolls_and_a_second_payment_does_not(): void
    {
        [$class, $session] = $this->paidSession(40, 5);
        $fulfillment = app(EnrollmentFulfillment::class);

        $this->actingAs($this->learner)
            ->get(route('payment.success', ['session_id' => $session['id']]))
            ->assertRedirect(route('dashboard'));

        $booking = Booking::query()->where('class_id', $class->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame($this->learner->id, $booking->user_id);
        $this->assertSame('user:'.$this->learner->id.':class:'.$class->id, $booking->active_enrollment_key);
        $this->assertSame(4, (int) $class->fresh()->capacity);
        $this->assertSame(1, Payment::query()->where('stripe_session_id', $session['id'])->count());

        $again = $fulfillment->fulfillFromSession($session);
        $this->assertSame(EnrollmentFulfillment::FULFILLED, $again->outcome);
        $this->assertFalse($again->newlyGranted);
        $this->assertSame(1, Booking::query()->where('class_id', $class->id)->count());
        $this->assertSame(4, (int) $class->fresh()->capacity);
    }

    public function test_async_payment_succeeded_enrolls_when_the_session_is_paid(): void
    {
        [$class, $session] = $this->paidSession(22, 3);
        $this->postWebhook('checkout.session.async_payment_succeeded', $session, 'evt_async_paid');

        $this->assertSame(1, Booking::query()->where('class_id', $class->id)->where('payment_status', 'paid')->count());
        $this->assertSame(2, (int) $class->fresh()->capacity);
    }

    public function test_the_wrong_user_cannot_claim_a_session(): void
    {
        [$class, $session] = $this->paidSession(40, 5);
        $other = $this->makeUser('other-pay@example.test', Role::LEARNER);

        $this->actingAs($other)
            ->get(route('payment.success', ['session_id' => $session['id']]))
            ->assertForbidden();

        $this->assertSame(0, Booking::query()->where('user_id', $other->id)->count());
        $this->assertSame(0, Booking::query()->where('class_id', $class->id)->count());
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_the_wrong_price_or_currency_is_recorded_without_access(): void
    {
        [$class, $session] = $this->paidSession(40, 5);
        $session['amount_total'] = 100;
        app(EnrollmentFulfillment::class)->fulfillFromSession($session);

        $this->assertSame(0, Booking::query()->where('class_id', $class->id)->count());
        $payment = Payment::query()->where('stripe_session_id', $session['id'])->first();
        $this->assertSame(Payment::STATUS_UNFULFILLABLE, $payment->status);
        $this->assertSame('amount_mismatch', $payment->resolution_reason);
        $this->assertTrue($payment->resolution_required);
        $this->assertNull($payment->booking_id);

        [$otherClass, $otherSession] = $this->paidSession(40, 5);
        $otherSession['currency'] = 'eur';
        app(EnrollmentFulfillment::class)->fulfillFromSession($otherSession);

        $this->assertSame(0, Booking::query()->where('class_id', $otherClass->id)->count());
        $this->assertSame(
            'currency_mismatch',
            Payment::query()->where('stripe_session_id', $otherSession['id'])->value('resolution_reason')
        );
    }

    public function test_a_duplicate_webhook_does_not_create_another_payment(): void
    {
        [, $session] = $this->paidSession(40, 5);

        $this->postWebhook('checkout.session.completed', $session, 'evt_once');
        $this->postWebhook('checkout.session.completed', $session, 'evt_once')->assertOk();

        $this->assertSame(1, Payment::query()->where('stripe_session_id', $session['id'])->count());
        $this->assertSame(1, StripeEvent::query()->where('event_id', 'evt_once')->count());
    }

    public function test_two_payments_do_not_create_duplicate_active_enrollment(): void
    {
        [$class, $first] = $this->paidSession(40, 2);
        $second = $this->sessionFor($class, 'cs_second', 'pi_second');
        $fulfillment = app(EnrollmentFulfillment::class);

        $fulfillment->fulfillFromSession($first);
        $duplicate = $fulfillment->fulfillFromSession($second);

        $this->assertSame(EnrollmentFulfillment::UNFULFILLABLE, $duplicate->outcome);
        $this->assertSame('duplicate_enrollment', $duplicate->reason);
        $this->assertSame(1, Booking::query()->where('class_id', $class->id)->where('payment_status', 'paid')->count());
        $this->assertSame(1, (int) $class->fresh()->capacity);
        $this->assertSame(2, Payment::query()->where('user_id', $this->learner->id)->count());
    }

    public function test_capacity_never_becomes_negative_and_a_full_class_is_recorded(): void
    {
        [$class, $session] = $this->paidSession(40, 0);
        $result = app(EnrollmentFulfillment::class)->fulfillFromSession($session);

        $this->assertSame(EnrollmentFulfillment::UNFULFILLABLE, $result->outcome);
        $this->assertSame('capacity_exhausted', $result->reason);
        $this->assertSame(0, Booking::query()->where('class_id', $class->id)->count());
        $this->assertSame(0, (int) $class->fresh()->capacity);
        $this->assertTrue((bool) $result->payment->resolution_required);
        $this->assertNull($result->payment->booking_id);
    }

    public function test_refunding_a_duplicate_purchase_preserves_the_original_enrollment(): void
    {
        [$class, $first] = $this->paidSession(40, 3);
        $second = $this->sessionFor($class, 'cs_dup', 'pi_dup');
        $fulfillment = app(EnrollmentFulfillment::class);
        $fulfillment->fulfillFromSession($first);
        $fulfillment->fulfillFromSession($second);
        $capacity = (int) $class->fresh()->capacity;

        $this->postWebhook('charge.refunded', [
            'id' => 'ch_dup',
            'payment_intent' => 'pi_dup',
            'amount' => 4000,
            'amount_refunded' => 4000,
            'currency' => 'usd',
            'refunded' => true,
        ], 'evt_refund_dup');

        $original = Booking::query()->where('class_id', $class->id)->first();
        $this->assertSame('paid', $original->payment_status);
        $this->assertNotNull($original->active_enrollment_key);
        $this->assertSame(
            Payment::STATUS_REFUNDED,
            Payment::query()->where('stripe_payment_intent_id', 'pi_dup')->value('status')
        );
        $this->assertSame($capacity, (int) $class->fresh()->capacity);
        $this->assertSame($this->learner->id, $original->user_id);
    }

    public function test_a_full_refund_revokes_access_without_releasing_the_seat(): void
    {
        [$class, $session] = $this->paidSession(40, 4);
        app(EnrollmentFulfillment::class)->fulfillFromSession($session);
        $capacity = (int) $class->fresh()->capacity;

        $this->postWebhook('charge.refunded', [
            'id' => 'ch_full',
            'payment_intent' => $session['payment_intent'],
            'amount' => 4000,
            'amount_refunded' => 4000,
            'currency' => 'usd',
            'refunded' => true,
        ], 'evt_refund_full');

        $booking = Booking::query()->where('class_id', $class->id)->first();
        $this->assertSame('refunded', $booking->payment_status);
        $this->assertNull($booking->active_enrollment_key);
        $this->assertSame($capacity, (int) $class->fresh()->capacity);
        $payment = Payment::query()->where('stripe_session_id', $session['id'])->first();
        $this->assertSame(Payment::STATUS_REFUNDED, $payment->status);
        $this->assertSame('seat_not_released', $payment->resolution_reason);
        $this->assertTrue($payment->resolution_required);

        try {
            app(ContentAccess::class)->authorizeCourseContent($this->learner, Course::query()->find($class->course_id));
            $this->fail('Refunded enrollment still grants access.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_a_later_completion_event_does_not_restore_refunded_access(): void
    {
        [$class, $session] = $this->paidSession(40, 4);
        $fulfillment = app(EnrollmentFulfillment::class);
        $fulfillment->fulfillFromSession($session);
        $fulfillment->applyChargeRefund([
            'payment_intent' => $session['payment_intent'],
            'amount' => 4000,
            'amount_refunded' => 4000,
            'currency' => 'usd',
            'refunded' => true,
        ]);

        $this->postWebhook('checkout.session.completed', $session, 'evt_late_complete');

        $this->assertSame('refunded', Booking::query()->where('class_id', $class->id)->value('payment_status'));
        $this->assertNull(Booking::query()->where('class_id', $class->id)->value('active_enrollment_key'));
        $this->assertSame(1, Booking::query()->where('class_id', $class->id)->count());
    }

    public function test_an_earlier_full_refund_blocks_the_later_completion(): void
    {
        [$class, $session] = $this->paidSession(40, 4);

        $this->postWebhook('charge.refunded', [
            'payment_intent' => $session['payment_intent'],
            'amount' => 4000,
            'amount_refunded' => 4000,
            'currency' => 'usd',
            'refunded' => true,
        ], 'evt_refund_first');
        $this->postWebhook('checkout.session.completed', $session, 'evt_complete_after_refund');

        $this->assertSame(0, Booking::query()->where('class_id', $class->id)->count());
        $this->assertSame(
            Payment::STATUS_REFUNDED,
            Payment::query()->where('stripe_session_id', $session['id'])->value('status')
        );
        $this->assertSame(4, (int) $class->fresh()->capacity);
    }

    public function test_a_partial_refund_keeps_the_enrollment(): void
    {
        [$class, $session] = $this->paidSession(40, 4);
        app(EnrollmentFulfillment::class)->fulfillFromSession($session);
        $capacity = (int) $class->fresh()->capacity;

        $this->postWebhook('charge.refunded', [
            'payment_intent' => $session['payment_intent'],
            'amount' => 4000,
            'amount_refunded' => 1500,
            'currency' => 'usd',
            'refunded' => false,
        ], 'evt_partial');

        $booking = Booking::query()->where('class_id', $class->id)->first();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertNotNull($booking->active_enrollment_key);
        $payment = Payment::query()->where('stripe_session_id', $session['id'])->first();
        $this->assertSame(Payment::STATUS_PARTIALLY_REFUNDED, $payment->status);
        $this->assertSame(1500, (int) $payment->refunded_cents);
        $this->assertFalse($payment->resolution_required);
        $this->assertSame($capacity, (int) $class->fresh()->capacity);
        app(ContentAccess::class)->authorizeCourseContent($this->learner, Course::query()->find($class->course_id));
        $this->assertTrue(true);
    }

    public function test_free_enrollment_still_works_and_forged_paid_input_does_not_unlock_content(): void
    {
        $free = $this->makeClass(0, 2);
        $free->update(['is_paid' => 'free', 'price' => null]);

        Livewire::actingAs($this->learner)
            ->test(CourseDetails::class, ['id' => $free->course_id])
            ->set('isPaid', true)
            ->set('price', 90)
            ->call('enroll', $free->id, true, 90);

        $this->assertSame(['free'], Booking::query()->where('class_id', $free->id)->pluck('payment_status')->all());
        $this->assertSame(1, (int) $free->fresh()->capacity);

        $paid = $this->makeClass(55, 4);
        Livewire::actingAs($this->learner)
            ->test(CourseDetails::class, ['id' => $paid->course_id])
            ->set('isPaid', false)
            ->set('price', 0)
            ->call('enroll', $paid->id, false, 0);

        $this->assertSame(0, Booking::query()->where('class_id', $paid->id)->count());
        $this->assertSame(0, Payment::query()->where('user_id', $this->learner->id)->where('amount_cents', 0)->count());
    }

    public function test_expired_and_failed_sessions_do_not_enroll(): void
    {
        [$class, $session] = $this->paidSession(40, 5);
        $this->postWebhook('checkout.session.expired', $session, 'evt_expired');
        $this->postWebhook('checkout.session.async_payment_failed', $session, 'evt_failed');

        $this->assertSame(0, Booking::query()->where('class_id', $class->id)->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(CheckoutSnapshot::FAILED, CheckoutSnapshot::query()->where('stripe_session_id', $session['id'])->value('state'));
    }

    public function test_an_invalid_webhook_signature_writes_nothing(): void
    {
        $before = $this->financialCounts();

        $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't=1,v1=deadbeef',
            ],
            '{"id":"evt_bad","type":"checkout.session.completed"}'
        )->assertStatus(400);

        $this->assertSame($before, $this->financialCounts());
    }

    public function test_a_paid_session_with_mismatched_metadata_is_recorded_without_access(): void
    {
        [$class, $session] = $this->paidSession(40, 5);
        $session['metadata']['user_id'] = (string) $this->owner->id;
        $this->stripe->sessions[$session['id']] = $session;

        $result = app(EnrollmentFulfillment::class)->fulfillFromSession($session);

        $this->assertSame(EnrollmentFulfillment::UNFULFILLABLE, $result->outcome);
        $this->assertSame('metadata_mismatch', $result->reason);
        $this->assertSame(0, Booking::query()->where('class_id', $class->id)->count());
        $this->assertSame(5, (int) $class->fresh()->capacity);
        $payment = Payment::query()->where('stripe_session_id', $session['id'])->first();
        $this->assertNotNull($payment);
        $this->assertNull($payment->booking_id);
        $this->assertSame(Payment::STATUS_UNFULFILLABLE, $payment->status);
        $this->assertSame($this->learner->id, $payment->user_id);
        $this->assertTrue($payment->resolution_required);
        $this->assertSame(4000, $payment->amount_cents);
    }

    public function test_an_unbound_snapshot_cannot_be_claimed_by_mismatched_metadata(): void
    {
        $class = $this->makeClass(40, 5);
        $snapshot = CheckoutSnapshot::query()->create([
            'user_id' => $this->learner->id,
            'item_type' => 'class',
            'item_id' => $class->id,
            'amount_cents' => 4000,
            'currency' => 'usd',
            'state' => CheckoutSnapshot::OPEN,
        ]);

        $result = app(EnrollmentFulfillment::class)->fulfillFromSession([
            'id' => 'cs_unbound_mismatch',
            'payment_status' => 'paid',
            'status' => 'complete',
            'amount_total' => 4000,
            'currency' => 'usd',
            'payment_intent' => 'pi_unbound_mismatch',
            'metadata' => [
                'user_id' => (string) $this->owner->id,
                'item_type' => 'class',
                'item_id' => (string) $class->id,
                'snapshot_id' => (string) $snapshot->id,
            ],
        ]);

        $this->assertSame(EnrollmentFulfillment::REJECTED, $result->outcome);
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(0, Booking::query()->count());
        $this->assertNull($snapshot->fresh()->stripe_session_id);
        $this->assertSame(CheckoutSnapshot::OPEN, $snapshot->fresh()->state);
    }

    public function test_a_free_booking_blocks_a_second_active_paid_enrollment(): void
    {
        [$class, $session] = $this->paidSession(40, 5);
        Booking::query()->create([
            'user_id' => $this->learner->id,
            'class_id' => $class->id,
            'payment_status' => 'free',
            'type' => 'automated',
            'order_number' => 'FREE_EXISTING',
        ]);

        $result = app(EnrollmentFulfillment::class)->fulfillFromSession($session);

        $this->assertSame(EnrollmentFulfillment::UNFULFILLABLE, $result->outcome);
        $this->assertSame('duplicate_enrollment', $result->reason);
        $this->assertSame(1, Booking::query()->where('class_id', $class->id)->where('payment_status', 'free')->count());
        $this->assertSame(0, Booking::query()->where('class_id', $class->id)->where('payment_status', 'paid')->count());
        $this->assertSame(5, (int) $class->fresh()->capacity);
        $this->assertSame(
            Payment::STATUS_UNFULFILLABLE,
            Payment::query()->where('stripe_session_id', $session['id'])->value('status')
        );
    }

    public function test_a_full_refund_flag_revokes_access_when_the_refunded_amount_is_omitted(): void
    {
        [$class, $session] = $this->paidSession(40, 4);
        app(EnrollmentFulfillment::class)->fulfillFromSession($session);
        $capacity = (int) $class->fresh()->capacity;

        $this->postWebhook('charge.refunded', [
            'id' => 'ch_flag',
            'payment_intent' => $session['payment_intent'],
            'amount' => 4000,
            'amount_refunded' => 0,
            'currency' => 'usd',
            'refunded' => true,
        ], 'evt_refund_flag')->assertOk();

        $booking = Booking::query()->where('class_id', $class->id)->first();
        $this->assertSame('refunded', $booking->payment_status);
        $this->assertNull($booking->active_enrollment_key);
        $this->assertSame($capacity, (int) $class->fresh()->capacity);
        $payment = Payment::query()->where('stripe_session_id', $session['id'])->first();
        $this->assertSame(Payment::STATUS_REFUNDED, $payment->status);
        $this->assertSame(4000, $payment->refunded_cents);

        $this->postWebhook('checkout.session.completed', $session, 'evt_after_flag_refund')->assertOk();
        $this->assertSame('refunded', $booking->fresh()->payment_status);
        $this->assertNull($booking->fresh()->active_enrollment_key);
    }

    public function test_a_stripe_create_failure_marks_the_snapshot_failed_and_writes_no_payment(): void
    {
        $class = $this->makeClass(40, 5);
        $this->stripe->failCreate = true;

        try {
            app(\App\Services\CheckoutSessionFactory::class)->forClass($class, $this->learner);
            $this->fail('Stripe failure was ignored.');
        } catch (\RuntimeException) {
            $snapshot = CheckoutSnapshot::query()->where('item_id', $class->id)->first();
            $this->assertNotNull($snapshot);
            $this->assertSame(CheckoutSnapshot::FAILED, $snapshot->state);
            $this->assertNull($snapshot->stripe_session_id);
            $this->assertSame(0, Payment::query()->count());
            $this->assertSame(0, Booking::query()->where('class_id', $class->id)->count());
        }
    }

    public function test_deleting_a_booking_keeps_its_payment_record(): void
    {
        [$class, $session] = $this->paidSession(40, 4);
        app(EnrollmentFulfillment::class)->fulfillFromSession($session);
        $booking = Booking::query()->where('class_id', $class->id)->first();
        $paymentId = Payment::query()->where('booking_id', $booking->id)->value('id');

        $booking->delete();

        $payment = Payment::query()->find($paymentId);
        $this->assertNotNull($payment);
        $this->assertNull($payment->booking_id);
        $this->assertSame(4000, $payment->amount_cents);
    }

    public function test_zero_price_checkout_creates_no_snapshot(): void
    {
        $class = $this->makeClass(0, 5);
        $class->update(['is_paid' => 'paid', 'price' => 0]);

        try {
            app(\App\Services\CheckoutSessionFactory::class)->forClass($class->fresh(), $this->learner);
            $this->fail('A zero price was sent to Stripe.');
        } catch (\RuntimeException) {
            $this->assertSame(0, CheckoutSnapshot::query()->where('item_id', $class->id)->count());
            $this->assertSame([], $this->stripe->created);
        }
    }

    private function postWebhook(string $type, array $object, string $eventId)
    {
        $payload = json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => $object],
        ], JSON_UNESCAPED_SLASHES);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, self::SECRET);

        return $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
            ],
            $payload
        );
    }

    private function financialCounts(): array
    {
        return [
            Payment::query()->count(),
            Booking::query()->count(),
            CheckoutSnapshot::query()->count(),
            StripeEvent::query()->count(),
            StripeRefundNotice::query()->count(),
        ];
    }

    private function paidSession(int $dollars, int $capacity): array
    {
        $class = $this->makeClass($dollars, $capacity);

        return [$class, $this->sessionFor($class, 'cs_'.$class->id.'_'.bin2hex(random_bytes(3)), 'pi_'.$class->id.'_'.bin2hex(random_bytes(3)))];
    }

    private function sessionFor(Classe $class, string $sessionId, string $intentId): array
    {
        $snapshot = CheckoutSnapshot::query()->create([
            'user_id' => $this->learner->id,
            'item_type' => 'class',
            'item_id' => $class->id,
            'amount_cents' => (int) round(((float) $class->price) * 100),
            'currency' => 'usd',
            'stripe_session_id' => $sessionId,
            'state' => CheckoutSnapshot::OPEN,
        ]);

        $session = [
            'id' => $sessionId,
            'payment_status' => 'paid',
            'status' => 'complete',
            'amount_total' => (int) $snapshot->amount_cents,
            'currency' => 'usd',
            'payment_intent' => $intentId,
            'metadata' => [
                'user_id' => (string) $this->learner->id,
                'item_type' => 'class',
                'item_id' => (string) $class->id,
                'snapshot_id' => (string) $snapshot->id,
            ],
        ];
        $this->stripe->sessions[$sessionId] = $session;

        return $session;
    }

    private function makeClass(int $dollars, int $capacity): Classe
    {
        $course = Course::query()->create([
            'user_id' => $this->owner->id,
            'course_type' => 'recorded',
            'is_paid' => 'paid',
            'price' => $dollars,
            'title' => 'Paid Course '.bin2hex(random_bytes(3)),
            'description' => 'Description',
            'thumbnail' => 'thumb.png',
            'video_path' => 'promo.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
        ]);

        return Classe::query()->create([
            'user_id' => $this->owner->id,
            'course_id' => $course->id,
            'title' => 'Paid Class '.bin2hex(random_bytes(3)),
            'description' => 'Class description',
            'visibility' => true,
            'class_type' => 'onsite',
            'is_paid' => $dollars > 0 ? 'paid' : 'free',
            'price' => $dollars > 0 ? $dollars : null,
            'capacity' => $capacity,
            'class_date' => '2026-12-20',
            'class_time' => '10:00:00',
            'booking_start_date' => '2026-11-01',
            'booking_end_date' => '2026-12-01',
        ]);
    }

    private function makeUser(string $email, string $roleName): User
    {
        return User::query()->create([
            'email' => str_replace('@', '-'.bin2hex(random_bytes(3)).'@', $email),
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'role_id' => Role::findByName($roleName)->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
    }
}

class FakeStripeGateway implements StripeGateway
{
    public array $sessions = [];

    public array $created = [];

    public bool $failCreate = false;

    public function createCheckoutSession(array $params): array
    {
        if ($this->failCreate) {
            throw new \RuntimeException('Stripe is unavailable.');
        }

        $this->created[] = $params;

        return ['id' => 'cs_created', 'url' => 'https://checkout.test/session'];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        if (! isset($this->sessions[$sessionId])) {
            throw new \RuntimeException('Unknown Checkout Session.');
        }

        return $this->sessions[$sessionId];
    }
}
