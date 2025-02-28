<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Tutor;

class TutorRejectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tutor;
    public $reason;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Tutor $tutor, $reason)
    {
        $this->tutor = $tutor;
        $this->reason = $reason;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Tutor Request Has Been Rejected')
            ->view('emails.tutor-rejection')
            ->with([
                'tutor' => $this->tutor,
                'reason' => $this->reason,
            ]);
    }
}
