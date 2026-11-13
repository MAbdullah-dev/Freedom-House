<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PAID = 'paid';

    public const STATUS_UNFULFILLABLE = 'unfulfillable';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    protected $fillable = [
        'amount',
        'booking_id',
        'payment_method',
        'status',
        'transaction_id',
        'checkout_snapshot_id',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'amount_cents',
        'currency',
        'refunded_cents',
        'resolution_required',
        'resolution_reason',
        'user_id',
    ];

    protected $casts = [
        'resolution_required' => 'boolean',
        'amount_cents' => 'integer',
        'refunded_cents' => 'integer',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
