<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class GoogleCalendarService
{
    /**
     * Generate Google Calendar invite link based on the model type.
     *
     * @param $model
     * @param string $type
     * @return string
     */
    public function generateCalendarLink($model, $type)
    {
        $startDateTime = Carbon::parse($model->date . ' ' . $model->time)->format('Ymd\THis\Z');
        $endDateTime = Carbon::parse($model->date . ' ' . $model->time)->addHour()->format('Ymd\THis\Z');

        $title = isset($model->title) ? $model->title : 'Untitled';
        $description = isset($model->description) ? $model->description : 'No description provided.';
        $location = isset($model->location) ? $model->location : 'Virtual';

        return 'https://www.google.com/calendar/render?action=TEMPLATE&text=' . urlencode($title) .
            '&dates=' . $startDateTime . '/' . $endDateTime .
            '&details=' . urlencode($description) .
            '&location=' . urlencode($location) .
            '&type=' . urlencode($type);
    }

    /**
     * Send Google Calendar invite via raw email.
     *
     * @param $user
     * @param $model
     * @param string $type
     * @return void
     */
    public function sendInvite($user, $model, $type)
    {
        $calendarLink = $this->generateCalendarLink($model, $type);

        $subject = 'You are invited to a ' . ucfirst($type) . ': ' . $model->title;
        $body = "
                 <h2>You're Invited to a " . ucfirst($type) . ": {$model->title}</h2>
                 <p>" . (isset($model->description) ? $model->description : 'No description provided.') . "</p>
                 <p>Click the link below to add this " . $type . " to your Google Calendar:</p>
                 <a href='{$calendarLink}' target='_blank'>Add to Google Calendar</a>
                 ";

        // Send the raw HTML email
        Mail::send([], [], function ($message) use ($user, $subject, $body) {
            $message->to($user->email)
                ->subject($subject)
                ->setBody($body, 'text/html');
        });
    }
}
