<?php

namespace App\Services;

use App\Models\Booking;

class EnrollmentResult
{
    public function __construct(
        public string $status,
        public ?Booking $booking = null,
        public mixed $item = null,
    ) {}
}
