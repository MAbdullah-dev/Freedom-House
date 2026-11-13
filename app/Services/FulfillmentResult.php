<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;

class FulfillmentResult
{
    public function __construct(
        public string $outcome,
        public ?Payment $payment = null,
        public ?Booking $booking = null,
        public ?string $reason = null,
        public bool $newlyGranted = false,
    ) {}
}
