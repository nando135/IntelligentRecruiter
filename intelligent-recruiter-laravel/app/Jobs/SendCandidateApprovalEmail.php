<?php

namespace App\Jobs;

use App\Mail\CandidateApprovedMail;
use App\Models\ApprovedCandidate;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCandidateApprovalEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $approvedCandidateId
    ) {
    }

    public function handle(): void
    {
        $approvedCandidate = ApprovedCandidate::with(['candidate', 'emailTemplate'])
            ->findOrFail($this->approvedCandidateId);

        $candidate = $approvedCandidate->candidate;

        if (! $candidate) {
            return;
        }

        $template = $approvedCandidate->emailTemplate ?: EmailTemplate::getActiveOrDefault();

        $subject = $template->renderSubject($candidate);
        $body = $template->renderBody($candidate);

        if (! $candidate->email) {
            $errorMessage = 'Candidate does not have an email address.';

            $approvedCandidate->update([
                'email_status' => 'failed',
                'email_error' => $errorMessage,
            ]);

            $candidate->update([
                'email_status' => 'failed',
                'email_error' => $errorMessage,
            ]);

            EmailLog::create([
                'candidate_id' => $candidate->id,
                'approved_candidate_id' => $approvedCandidate->id,
                'email_template_id' => $template->id,
                'recipient_email' => '-',
                'subject' => $subject,
                'body' => $body,
                'status' => 'failed',
                'error_message' => $errorMessage,
                'sent_at' => null,
            ]);

            return;
        }

        try {
            Mail::to($candidate->email)->send(new CandidateApprovedMail($subject, $body));

            $now = now();

            $approvedCandidate->update([
                'email_status' => 'sent',
                'email_sent_at' => $now,
                'email_error' => null,
            ]);

            $candidate->update([
                'email_status' => 'sent',
                'email_sent_at' => $now,
                'email_error' => null,
            ]);

            EmailLog::create([
                'candidate_id' => $candidate->id,
                'approved_candidate_id' => $approvedCandidate->id,
                'email_template_id' => $template->id,
                'recipient_email' => $candidate->email,
                'subject' => $subject,
                'body' => $body,
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => $now,
            ]);
        } catch (Throwable $e) {
            $approvedCandidate->update([
                'email_status' => 'failed',
                'email_error' => $e->getMessage(),
            ]);

            $candidate->update([
                'email_status' => 'failed',
                'email_error' => $e->getMessage(),
            ]);

            EmailLog::create([
                'candidate_id' => $candidate->id,
                'approved_candidate_id' => $approvedCandidate->id,
                'email_template_id' => $template->id,
                'recipient_email' => $candidate->email,
                'subject' => $subject,
                'body' => $body,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at' => null,
            ]);
        }
    }
}
