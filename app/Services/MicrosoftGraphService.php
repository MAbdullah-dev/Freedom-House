<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\Events;
use App\Models\User;
use App\Services\Microsoft\GraphAttendeeSync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphService
{
    public function refreshAccessToken(User $user)
    {
        try {
            $refreshToken = $user->refresh_token;

            if (!$refreshToken) {
                throw new \Exception('No refresh token found in session.');
            }

            $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'client_id' => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

            if ($response->failed()) {
                Log::error('Failed to refresh Microsoft access token.', ['status' => $response->status()]);
                throw new \Exception('Failed to refresh access token.');
            }

            $newAccessToken = $response->json('access_token');
            $newExpiryTime = now()->addSeconds($response->json('expires_in'));

            $user->update([
                'token_expires_at' => $newExpiryTime,
            ]);

            return $newAccessToken;
        } catch (\Exception $e) {
            Log::error('Microsoft access token refresh failed.', ['exception' => $e::class]);
            if (in_array($e->getMessage(), ['No refresh token found in session.', 'Failed to refresh access token.'], true)) {
                throw $e;
            }

            throw new \RuntimeException('Failed to refresh access token.');
        }
    }
    public function createCalendarEventWithTeamsMeeting(Classe|Events $model, array $attendees)
    {
        try {
            $accessToken = $this->refreshAccessToken(Auth::user());

            $userTimezone = Auth::user()->timezone;
            $startTime = Carbon::parse($model->class_date . ' ' . $model->class_time, 'UTC')->setTimezone($userTimezone);
            $endTime = $startTime->copy()->addHours(1);

            $eventData = [
                'subject' => $model->title,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $model->description ?? 'No description provided.',
                ],
                'start' => [
                    'dateTime' => $model->class_date . 'T' . $model->class_time,
                    'timeZone' => $userTimezone,
                ],
                'end' => [
                    'dateTime' => $model->class_date . 'T' . $model->class_time,
                    'timeZone' => $userTimezone,
                ],
                'location' => [
                    'displayName' => 'Online',
                ],
                'attendees' => $this->formatAttendees($attendees),
                'allowNewTimeProposals' => true,
                'isOnlineMeeting' => true,
                'onlineMeetingProvider' => 'teamsForBusiness',
            ];

            $response = Http::withToken($accessToken)->post('https://graph.microsoft.com/v1.0/me/events', $eventData);
            if ($response->failed()) {
                $this->failCalendar('created', $response->status());
            }


            $meetingLink = $response->json('onlineMeeting.joinUrl');
            $microsoft_event_id = $response->json('id');
            $model->update([
                'microsoft_event_id' => $microsoft_event_id,
                'teams_link' => $meetingLink,
            ]);

            return true;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->failCalendar('created');
        }
    }

    private function formatAttendees(array $attendees)
    {
        return array_map(function ($attendee) {
            return [
                'emailAddress' => [
                    'address' => $attendee['email'],
                    'name' => $attendee['name'],
                ],
                'type' => 'optional',
            ];
        }, $attendees);
    }

    public function createCalendarEvent(Classe|Events $model)
    {
        try {
            $accessToken = $this->refreshAccessToken(Auth::user());
            $userTimezone = Auth::user()->timezone ?? 'UTC';
            $durationInHours = 1;
            $startDateTime = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $model->class_date . 'T' . $model->class_time, 'America/New_York');
            $endDateTime = $startDateTime->copy()->addHours($durationInHours);

            $eventData = [
                'subject' => $model->title,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $model->description ?? 'No description provided.',
                ],
                'start' => [
                    'dateTime' => $startDateTime->toIso8601String(),
                    'timeZone' => $userTimezone,
                ],
                'end' => [
                    'dateTime' => $endDateTime->toIso8601String(),
                    'timeZone' => $userTimezone,
                ],
                'location' => [
                    'displayName' => $model->onsite_address,
                ],
            ];

            $response = Http::withToken($accessToken)->post('https://graph.microsoft.com/v1.0/me/events', $eventData);

            if ($response->failed()) {
                $this->failCalendar('created', $response->status());
            }

            $microsoft_event_id = $response->json('id');
            $model->update([
                'microsoft_event_id' => $microsoft_event_id,
            ]);

            return true;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->failCalendar('created');
        }
    }
    public function addDynamicAttendees(Classe|Events $model, array $attendees, $organizerId)
    {
        return $this->syncAttendees($model, $attendees, $organizerId, 'append');
    }

    public function applicationAccessToken(): string
    {
        return $this->getAccessTokenWithPermissions();
    }
    private function getAccessTokenWithPermissions()
    {
        $tenantId = (string) config('services.microsoft.tenant');

        $tokenResponse = Http::asForm()->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
            'client_id' => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type' => 'client_credentials',
            'scope' => 'https://graph.microsoft.com/.default',
        ]);

        if ($tokenResponse->failed()) {
            Log::error('Failed to retrieve Microsoft application token.', ['status' => $tokenResponse->status()]);
            throw new \Exception('Failed to retrieve access token.');
        }

        return $tokenResponse->json('access_token');
    }

    public function createUserCalendarEvent($title, $description, $startDateTime, $endDateTime, $location, $attendees)
    {
        try {
            $accessToken = $this->refreshAccessToken(Auth::user());

            $userTimezone = Auth::user()->timezone ?? 'UTC';

            $eventData = [
                'subject' => $title,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $description ?? 'No description provided.',
                ],
                'start' => [
                    'dateTime' => Carbon::parse($startDateTime, 'UTC')->setTimezone($userTimezone)->toIso8601String(),
                    'timeZone' => $userTimezone,
                ],
                'end' => [
                    'dateTime' => Carbon::parse($endDateTime, 'UTC')->setTimezone($userTimezone)->toIso8601String(),
                    'timeZone' => $userTimezone,
                ],
                'location' => [
                    'displayName' => $location,
                ],
                'attendees' => $this->formatAttendees($attendees),
                'allowNewTimeProposals' => true,
                'isOnlineMeeting' => false,
            ];

            $response = Http::withToken($accessToken)->post('https://graph.microsoft.com/v1.0/me/events', $eventData);

            if ($response->failed()) {
                $this->failCalendar('created', $response->status());
            }

            return true;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->failCalendar('created');
        }
    }

    public function updateCalendarEvent($eventId, $title, $description, $startDateTime, $endDateTime, $location, $attendees, $isOnlineMeeting = false)
    {
        try {
            if (! is_string($eventId) || $eventId === '' || preg_match('/[\s?#\\\\]/', $eventId) || str_contains($eventId, '..')) {
                $this->failCalendar('updated');
            }

            $accessToken = $this->refreshAccessToken(Auth::user());
            $userTimezone = Auth::user()->timezone ?? 'UTC';
            $url = 'https://graph.microsoft.com/v1.0/me/events/'.rawurlencode($eventId);

            for ($attempt = 0; $attempt < 3; $attempt++) {
                $existingEventResponse = Http::withToken($accessToken)->get($url);
                if ($existingEventResponse->failed()) {
                    $this->failCalendar('updated', $existingEventResponse->status());
                }

                $existingEvent = $existingEventResponse->json();
                $etag = is_array($existingEvent) ? ($existingEvent['@odata.etag'] ?? null) : null;
                if (! is_string($etag) || $etag === '') {
                    Log::error('Microsoft event is missing an etag.');
                    $this->failCalendar('updated');
                }

                $existingAttendees = is_array($existingEvent['attendees'] ?? null) ? $existingEvent['attendees'] : [];
                $eventData = [
                    'subject' => $title,
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $description ?? 'No description provided.',
                    ],
                    'start' => [
                        'dateTime' => Carbon::parse($startDateTime, 'UTC')->setTimezone($userTimezone)->toIso8601String(),
                        'timeZone' => $userTimezone,
                    ],
                    'end' => [
                        'dateTime' => Carbon::parse($endDateTime, 'UTC')->setTimezone($userTimezone)->toIso8601String(),
                        'timeZone' => $userTimezone,
                    ],
                    'location' => [
                        'displayName' => $location,
                    ],
                    'attendees' => app(GraphAttendeeSync::class)->mergeAttendees($existingAttendees, is_array($attendees) ? $attendees : []),
                    'allowNewTimeProposals' => false,
                ];

                if ($isOnlineMeeting) {
                    $eventData['isOnlineMeeting'] = true;
                    $eventData['onlineMeetingProvider'] = 'teamsForBusiness';
                }

                $response = Http::withToken($accessToken)
                    ->withHeaders(['If-Match' => $etag])
                    ->patch($url.'?sendUpdates=all', $eventData);

                if ($response->status() === 412) {
                    continue;
                }

                if ($response->failed()) {
                    $this->failCalendar('updated', $response->status());
                }

                return true;
            }

            $this->failCalendar('updated', 412);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->failCalendar('updated');
        }
    }

    public function updateDynamicAttendees(Classe|Events $model, array $attendees, $organizerId)
    {
        return $this->syncAttendees($model, $attendees, $organizerId, 'append');
    }

    public function deleteDynamicAttendees(Classe|Events $model, array $attendees, $organizerId)
    {
        return $this->syncAttendees($model, $attendees, $organizerId, 'remove');
    }

    private function syncAttendees(Classe|Events $model, array $attendees, mixed $organizerId, string $mode): bool
    {
        if (! is_string($model->microsoft_event_id) || $model->microsoft_event_id === '' || ! is_string($organizerId) || $organizerId === '') {
            $this->failCalendar('updated');
        }

        $result = $mode === 'remove'
            ? app(GraphAttendeeSync::class)->remove($organizerId, $model->microsoft_event_id, $attendees, $this->getAccessTokenWithPermissions())
            : app(GraphAttendeeSync::class)->append($organizerId, $model->microsoft_event_id, $attendees, $this->getAccessTokenWithPermissions());

        if (! $result->ok) {
            Log::error('Microsoft attendee sync failed.', ['code' => $result->code]);
            $this->failCalendar('updated');
        }

        return true;
    }

    private function failCalendar(string $action, ?int $status = null): never
    {
        Log::error('Microsoft calendar request failed.', [
            'action' => $action,
            'status' => $status,
        ]);

        throw new \RuntimeException(
            $action === 'created'
                ? 'The calendar event could not be created.'
                : 'The calendar event could not be updated.'
        );
    }
}
