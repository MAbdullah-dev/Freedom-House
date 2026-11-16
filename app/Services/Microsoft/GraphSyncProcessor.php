<?php

namespace App\Services\Microsoft;

use App\Models\Booking;
use App\Models\Classe;
use App\Models\Events;
use App\Models\GraphSyncOutbox;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

class GraphSyncProcessor
{
    public function enqueueAttendee(User $user, Booking $booking): void
    {
        $item = $booking->class_id
            ? Classe::query()->find($booking->class_id)
            : ($booking->event_id ? Events::query()->find($booking->event_id) : null);

        if ($item === null || ! is_string($item->microsoft_event_id) || $item->microsoft_event_id === '') {
            return;
        }

        $organizer = $item instanceof Events
            ? $item->users?->microsoft_id
            : $item->user?->microsoft_id;

        $email = strtolower(trim((string) $user->email));
        if (! is_string($organizer) || $organizer === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $key = hash('sha256', 'append:'.$item->microsoft_event_id.':'.$email);
        $attributes = [
            'booking_id' => $booking->id,
            'operation' => GraphSyncOutbox::OPERATION_APPEND,
            'microsoft_event_id' => $item->microsoft_event_id,
            'organizer_microsoft_id' => $organizer,
            'attendee_email' => $email,
            'attendee_name' => trim(((string) $user->first_name).' '.((string) $user->last_name)) ?: $email,
            'status' => GraphSyncOutbox::STATUS_PENDING,
            'attempts' => 0,
        ];

        try {
            $row = GraphSyncOutbox::query()->firstOrCreate(
                ['idempotency_key' => $key],
                $attributes,
            );
        } catch (UniqueConstraintViolationException) {
            $row = GraphSyncOutbox::query()->where('idempotency_key', $key)->first();
        }

        if ($row === null || $row->status === GraphSyncOutbox::STATUS_SUCCEEDED) {
            return;
        }

        $this->process($row);
    }

    public function process(GraphSyncOutbox $row): void
    {
        if ($row->status === GraphSyncOutbox::STATUS_SUCCEEDED || $row->operation !== GraphSyncOutbox::OPERATION_APPEND) {
            return;
        }

        try {
            $token = app(MicrosoftGraphService::class)->applicationAccessToken();
        } catch (Throwable) {
            $this->recordFailure($row, 'token_unavailable');

            return;
        }

        $result = new GraphSyncResult(false, 'update_failed');

        try {
            for ($attempt = 0; $attempt < 3; $attempt++) {
                $result = app(GraphAttendeeSync::class)->append(
                    $row->organizer_microsoft_id,
                    $row->microsoft_event_id,
                    [[
                        'email' => $row->attendee_email,
                        'name' => $row->attendee_name ?: $row->attendee_email,
                    ]],
                    $token,
                );

                if ($result->ok) {
                    $row->forceFill([
                        'status' => GraphSyncOutbox::STATUS_SUCCEEDED,
                        'last_error' => null,
                        'attempts' => $row->attempts + 1,
                        'available_at' => null,
                    ])->save();

                    return;
                }

                if ($result->code !== 'conflict') {
                    break;
                }
            }
        } catch (Throwable) {
            $this->recordFailure($row, 'update_failed');

            return;
        }

        $this->recordFailure($row, $result->code);
    }

    private function recordFailure(GraphSyncOutbox $row, string $code): void
    {
        $safe = preg_replace('/[^a-z0-9_]/', '', strtolower($code));
        $safe = is_string($safe) && $safe !== '' ? substr($safe, 0, 64) : 'update_failed';

        $row->forceFill([
            'status' => GraphSyncOutbox::STATUS_FAILED,
            'attempts' => $row->attempts + 1,
            'last_error' => $safe,
            'available_at' => now()->addMinutes(min(60, 2 ** min(6, $row->attempts))),
        ])->save();
    }
}
