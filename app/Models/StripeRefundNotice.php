<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeRefundNotice extends Model
{
    protected $fillable = [
        'payment_intent_id',
        'amount_refunded',
        'currency',
        'fully_refunded',
    ];

    protected $casts = [
        'fully_refunded' => 'boolean',
        'amount_refunded' => 'integer',
    ];
}
