<?php

namespace App\Http\Controllers;

use App\Services\EnrollmentFulfillment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, EnrollmentFulfillment $fulfillment): Response
    {
        $payload = $request->getContent();
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            return response('Webhook secret is not configured.', 400);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response('Invalid webhook signature.', 400);
        }

        $object = $event->data->object;
        $data = method_exists($object, 'toArray') ? $object->toArray() : [];
        $fulfillment->handleEvent((string) $event->id, (string) $event->type, $data);

        return response('ok', 200);
    }
}
