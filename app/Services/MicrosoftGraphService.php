<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\Events;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphService
{
    public function refreshAccessToken(User $user)
    {
        try {
            $refreshToken = session('refresh_token');

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

            $eventData = [
                'subject' => $model->title,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $model->description ?? 'No description provided.',
                ],
                'start' => [
                    'dateTime' => $model->class_date . 'T' . $model->class_time,
                    'timeZone' => 'America/New_York',
                ],
                'end' => [
                    'dateTime' => $model->class_date . 'T' . $model->class_time,
                    'timeZone' => 'America/New_York',
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

            $model->update([
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
                    'timeZone' => 'America/New_York',
                ],
                'end' => [
                    'dateTime' => $endDateTime->toIso8601String(),
                    'timeZone' => 'America/New_York',
                ],
                'location' => [
                    'displayName' => $model->onsite_address,
                ],
            ];

            $response = Http::withToken($accessToken)->post('https://graph.microsoft.com/v1.0/me/events', $eventData);
            // dd($response);

            if ($response->failed()) {
                dd($response->body());
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error creating calendar event: ' . $e->getMessage());
            throw $e;
            return false;
        }
    }
}
