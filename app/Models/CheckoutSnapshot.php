<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutSnapshot extends Model
{
    public const OPEN = 'open';

    public const FULFILLED = 'fulfilled';

    public const UNFULFILLABLE = 'unfulfillable';

    public const EXPIRED = 'expired';

    public const FAILED = 'failed';

    public const REFUNDED = 'refunded';

    public const PARTIALLY_REFUNDED = 'partially_refunded';

    protected $fillable = [
        'user_id',
        'item_type',
        'item_id',
        'amount_cents',
        'currency',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'state',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
