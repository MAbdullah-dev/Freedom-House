<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\Events;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphService
{
    public function refreshAccessToken(User $user)
    {
        try {
            $refreshToken = session('refresh_token');

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
                Log::error('Failed to refresh access token', ['response' => $response->json()]);
                throw new \Exception('Failed to refresh access token.');
            }

            $newAccessToken = $response->json('access_token');
            $newExpiryTime = now()->addSeconds($response->json('expires_in'));

            $user->update([
                'token_expires_at' => $newExpiryTime,
            ]);

            session(['access_token' => $newAccessToken]);

            return $newAccessToken;
        } catch (\Exception $e) {
            Log::error('Error refreshing access token: ' . $e->getMessage());
            throw $e;
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
            // dd($response);
            if ($response->failed()) {
                dd($response->body());
            }


            $meetingLink = $response->json('onlineMeeting.joinUrl');
            $microsoft_event_id = $response->json('id');
            $model->update([
                'microsoft_event_id' => $microsoft_event_id,
                'teams_link' => $meetingLink,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error creating calendar event with Teams meeting: ' . $e->getMessage());
            throw $e;
            return false;
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
                'type' => 'required',
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
                dd($response->body());
            }

            $microsoft_event_id = $response->json('id');
            $model->update([
                'microsoft_event_id' => $microsoft_event_id,
            ]);

            // dd($response);



            return true;
        } catch (\Exception $e) {
            Log::error('Error creating calendar event: ' . $e->getMessage());
            throw $e;
            return false;
        }
    }
    public function addDynamicAttendees(Classe|Events $model, array $attendees, $organizerId)
    {
        try {
            $accessToken = $this->refreshAccessToken(Auth::user());

            // Ensure the model has a valid Microsoft Event ID
            if (!$model->microsoft_event_id) {
                throw new \Exception('Microsoft Event ID not found.');
            }

            // Format attendees
            $attendeesData = $this->formatAttendees($attendees);
            // dd($organizerId, $model->microsoft_event_id, $attendeesData, $accessToken);
            // Add attendees to the event using the organizer's Microsoft ID
            // Fetch the event to ensure it exists
            $response = Http::withToken($accessToken)
                ->get('https://graph.microsoft.com/v1.0/users/' . $organizerId . '/events/' . $model->microsoft_event_id);

            // Check if the request failed
            if ($response->failed()) {
                // If the event doesn't exist, log or return the error message
                dd('Event not found: ' . $response->body());  // Debugging the error response
            } else {
                // If the event exists, proceed with adding attendees
                $event = $response->json();
                // Optionally log the event details for debugging
                Log::info('Event details: ', $event);

                // Now, you can safely proceed to update the event
                $updateResponse = Http::withToken($accessToken)
                    ->patch('https://graph.microsoft.com/v1.0/users/' . $organizerId . '/events/' . $model->microsoft_event_id, [
                        'attendees' => $attendeesData,
                    ]);

                if ($updateResponse->failed()) {
                    dd('Error updating event: ' . $updateResponse->body());
                }

                return true;  // Success
            }


            return true;
        } catch (\Exception $e) {
            Log::error('Error adding dynamic attendees: ' . $e->getMessage());
            throw $e;
            return false;
        }
    }
}
