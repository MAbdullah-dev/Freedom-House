<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GraphSyncOutbox extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const OPERATION_APPEND = 'append_attendee';

    protected $table = 'graph_sync_outbox';

    protected $fillable = [
        'booking_id',
        'operation',
        'idempotency_key',
        'microsoft_event_id',
        'organizer_microsoft_id',
        'attendee_email',
        'attendee_name',
        'status',
        'attempts',
        'last_error',
        'available_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'available_at' => 'datetime',
    ];
}
