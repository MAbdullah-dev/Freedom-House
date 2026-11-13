<?php

namespace App\Services;

use App\Models\CheckoutSnapshot;
use App\Models\Classe;
use App\Models\Course;
use App\Models\Events;
use App\Models\User;
use App\Services\Stripe\StripeGateway;
use RuntimeException;
use Throwable;

/**
 * Starts a Stripe Checkout Session from the database price and stores the snapshot.
 * It does not retrieve the session or grant enrollment.
 */
class CheckoutSessionFactory
{
    public function forClass(Classe $class, User $user): string
    {
        return $this->start($user, 'class', (int) $class->id, $class->title, $class->price);
    }

    public function forCourse(Course $course, User $user): string
    {
        return $this->start($user, 'course', (int) $course->id, $course->title, $course->price);
    }

    public function forEvent(Events $event, User $user): string
    {
        return $this->start($user, 'event', (int) $event->id, $event->title, $event->price);
    }

    private function start(User $user, string $type, int $id, string $name, mixed $price): string
    {
        $cents = (int) round(((float) $price) * 100);
        $currency = strtolower((string) config('services.stripe.currency', 'usd'));

        if ($cents <= 0 || strlen($currency) !== 3) {
            throw new RuntimeException('Checkout requires a positive server-side price.');
        }

        $snapshot = CheckoutSnapshot::query()->create([
            'user_id' => $user->id,
            'item_type' => $type,
            'item_id' => $id,
            'amount_cents' => $cents,
            'currency' => $currency,
            'state' => CheckoutSnapshot::OPEN,
        ]);

        try {
            $session = app(StripeGateway::class)->createCheckoutSession([
                'currency' => $currency,
                'product_name' => $name,
                'amount_cents' => $cents,
                'success_url' => route('payment.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'client_reference_id' => (string) $user->id,
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'item_type' => $type,
                    'item_id' => (string) $id,
                    'snapshot_id' => (string) $snapshot->id,
                ],
            ]);
        } catch (Throwable $exception) {
            $snapshot->update(['state' => CheckoutSnapshot::FAILED]);
            throw $exception;
        }

        $snapshot->update(['stripe_session_id' => $session['id']]);

        return $session['url'];
    }
}
