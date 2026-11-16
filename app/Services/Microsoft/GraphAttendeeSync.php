<?php

namespace App\Services\Microsoft;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GraphAttendeeSync
{
    public function append(string $organizerId, string $eventId, array $attendees, string $token): GraphSyncResult
    {
        return $this->mutate($organizerId, $eventId, $attendees, $token, 'append');
    }

    public function remove(string $organizerId, string $eventId, array $attendees, string $token): GraphSyncResult
    {
        return $this->mutate($organizerId, $eventId, $attendees, $token, 'remove');
    }

    private function mutate(string $organizerId, string $eventId, array $attendees, string $token, string $mode): GraphSyncResult
    {
        if (! $this->safeSegment($organizerId) || ! $this->safeSegment($eventId) || $token === '') {
            return new GraphSyncResult(false, 'invalid_identifier');
        }

        $url = 'https://graph.microsoft.com/v1.0/users/'.rawurlencode($organizerId).'/events/'.rawurlencode($eventId);
        $response = Http::withToken($token)->get($url);

        if ($response->failed()) {
            Log::error('Microsoft event read failed.', ['status' => $response->status()]);

            return new GraphSyncResult(false, 'read_failed');
        }

        $event = $response->json();
        if (! is_array($event)) {
            return new GraphSyncResult(false, 'read_failed');
        }

        $etag = $event['@odata.etag'] ?? null;
        if (! is_string($etag) || $etag === '') {
            $header = $response->header('ETag');
            $etag = is_string($header) && $header !== '' ? $header : null;
        }

        if (! is_string($etag) || $etag === '') {
            Log::error('Microsoft event is missing an etag.');

            return new GraphSyncResult(false, 'missing_etag');
        }

        $existing = is_array($event['attendees'] ?? null) ? $event['attendees'] : [];
        $merged = $mode === 'remove'
            ? $this->without($existing, $attendees)
            : $this->merge($existing, $attendees);

        if ($this->emails($existing) === $this->emails($merged)) {
            return new GraphSyncResult(true, 'already_present');
        }

        $patch = Http::withToken($token)
            ->withHeaders(['If-Match' => $etag])
            ->patch($url.'?sendUpdates=all', ['attendees' => $merged]);

        if ($patch->status() === 412) {
            return new GraphSyncResult(false, 'conflict');
        }

        if ($patch->failed()) {
            Log::error('Microsoft attendee update failed.', ['status' => $patch->status()]);

            return new GraphSyncResult(false, 'update_failed');
        }

        return new GraphSyncResult(true, 'updated');
    }

    public function mergeAttendees(array $existing, array $incoming): array
    {
        return $this->merge($existing, $incoming);
    }

    private function merge(array $existing, array $incoming): array
    {
        $merged = [];
        $seen = [];

        foreach ($existing as $attendee) {
            if (! is_array($attendee)) {
                continue;
            }

            $merged[] = $attendee;
            $email = $this->emailOf($attendee);
            if ($email !== null) {
                $seen[$email] = true;
            }
        }

        foreach ($incoming as $attendee) {
            if (! is_array($attendee)) {
                continue;
            }

            $email = $this->emailOf($attendee);
            if ($email === null || isset($seen[$email])) {
                continue;
            }

            $seen[$email] = true;
            $name = $attendee['name'] ?? ($attendee['emailAddress']['name'] ?? $email);
            $merged[] = [
                'emailAddress' => [
                    'address' => $email,
                    'name' => is_string($name) && $name !== '' ? $name : $email,
                ],
                'type' => 'optional',
            ];
        }

        return $merged;
    }

    private function without(array $existing, array $incoming): array
    {
        $remove = [];
        foreach ($incoming as $attendee) {
            if (! is_array($attendee)) {
                continue;
            }

            $email = $this->emailOf($attendee);
            if ($email !== null) {
                $remove[$email] = true;
            }
        }

        return array_values(array_filter($existing, function ($attendee) use ($remove) {
            if (! is_array($attendee)) {
                return false;
            }

            $email = $this->emailOf($attendee);

            return $email === null || ! isset($remove[$email]);
        }));
    }

    private function emails(array $attendees): array
    {
        $emails = [];
        foreach ($attendees as $attendee) {
            if (! is_array($attendee)) {
                continue;
            }

            $email = $this->emailOf($attendee);
            if ($email !== null) {
                $emails[] = $email;
            }
        }

        sort($emails);

        return $emails;
    }

    private function emailOf(array $attendee): ?string
    {
        $address = $attendee['emailAddress']['address'] ?? $attendee['email'] ?? null;
        if (! is_string($address)) {
            return null;
        }

        $address = strtolower(trim($address));

        return filter_var($address, FILTER_VALIDATE_EMAIL) ? $address : null;
    }

    private function safeSegment(string $value): bool
    {
        if ($value === '' || strlen($value) > 512) {
            return false;
        }

        return ! preg_match('/[\s?#\\\\]/', $value) && ! str_contains($value, '..');
    }
}
