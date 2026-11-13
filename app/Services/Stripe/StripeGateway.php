<?php

namespace App\Services\Stripe;

interface StripeGateway
{
    /**
     * @param  array<string, mixed>  $params
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(array $params): array;

    /**
     * @return array<string, mixed>
     */
    public function retrieveCheckoutSession(string $sessionId): array;
}
