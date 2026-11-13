<?php

namespace App\Services\Stripe;

use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeSdkGateway implements StripeGateway
{
    public function createCheckoutSession(array $params): array
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $params['currency'],
                    'unit_amount' => $params['amount_cents'],
                    'product_data' => [
                        'name' => $params['product_name'],
                    ],
                ],
            ]],
            'success_url' => $params['success_url'],
            'cancel_url' => $params['cancel_url'],
            'client_reference_id' => $params['client_reference_id'],
            'metadata' => $params['metadata'],
        ]);

        return [
            'id' => $session->id,
            'url' => $session->url,
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));
        $session = Session::retrieve($sessionId);
        $intent = $session->payment_intent;
        $metadata = $session->metadata ? $session->metadata->toArray() : [];

        return [
            'id' => $session->id,
            'payment_status' => $session->payment_status,
            'status' => $session->status,
            'amount_total' => $session->amount_total,
            'currency' => $session->currency,
            'payment_intent' => is_string($intent) ? $intent : ($intent->id ?? null),
            'metadata' => $metadata,
        ];
    }
}
