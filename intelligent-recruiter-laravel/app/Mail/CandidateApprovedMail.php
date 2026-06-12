<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CandidateApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText
    ) {
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.candidate-approved')
            ->with([
                'bodyText' => $this->bodyText,
            ]);
    }
}
