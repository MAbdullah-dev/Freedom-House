<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Tutor;

class TutorApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tutor;
    public $message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Tutor $tutor, $message)
    {
        $this->tutor = $tutor;
        $this->message = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Tutor Request Has Been Approved')
            ->view('emails.tutor-approval')
            ->with([
                'tutor' => $this->tutor,
                'message' => $this->message,
            ]);
    }
}
