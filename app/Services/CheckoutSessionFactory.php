<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\Course;
use App\Models\Events;
use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * Starts a Stripe Checkout Session from the database price.
 * It does not retrieve the session, write a payment, or grant enrollment.
 */
class CheckoutSessionFactory
{
    public function forClass(Classe $class): string
    {
        return $this->create(
            'Class Enrollment',
            $class->price,
            'class_id='.$class->id
        );
    }

    public function forCourse(Course $course): string
    {
        return $this->create(
            $course->title,
            $course->price,
            'course_id='.$course->id
        );
    }

    public function forEvent(Events $event): string
    {
        return $this->create(
            'Event Enrollment',
            $event->price,
            'event_id='.$event->id
        );
    }

    private function create(string $name, mixed $price, string $itemQuery): string
    {
        $cents = (int) round(((float) $price) * 100);

        if ($cents <= 0) {
            throw new \RuntimeException('Checkout requires a positive server-side price.');
        }

        session()->put('stripe_payment_amount', $price);
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $name,
                    ],
                    'unit_amount' => $cents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.success').'?session_id={CHECKOUT_SESSION_ID}&'.$itemQuery,
            'cancel_url' => route('payment.cancel').'?'.$itemQuery,
        ]);

        return $session->url;
    }
}
