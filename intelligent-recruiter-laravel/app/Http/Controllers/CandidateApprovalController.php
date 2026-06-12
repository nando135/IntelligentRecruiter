<?php

namespace App\Http\Controllers;

use App\Jobs\SendCandidateApprovalEmail;
use App\Models\ApprovedCandidate;
use App\Models\Candidate;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CandidateApprovalController extends Controller
{
    public function approve(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:2000'],
            'approval_source' => ['nullable', 'string', 'in:single,bulk,rank_based,hr_preference'],
        ]);

        if ($candidate->approval_status === 'approved') {
            return back()->with('error', 'This candidate is already approved.');
        }

        if (! $candidate->email) {
            return back()->with('error', 'This candidate cannot be approved because no email address was found.');
        }

        $template = EmailTemplate::getActiveOrDefault();

        $approvedCandidate = $this->approveCandidate(
            candidate: $candidate,
            template: $template,
            approvalSource: $validated['approval_source'] ?? 'single',
            approvalNote: $validated['approval_note'] ?? null
        );

        SendCandidateApprovalEmail::dispatch($approvedCandidate->id);

        return back()->with('success', 'Candidate approved successfully. Confirmation email has been queued.');
    }

    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'candidate_ids' => ['required', 'array', 'min:1'],
            'candidate_ids.*' => ['integer', 'exists:candidates,id'],
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $template = EmailTemplate::getActiveOrDefault();

        $candidates = Candidate::whereIn('id', $validated['candidate_ids'])->get();

        $approvedCount = 0;
        $queuedEmailCount = 0;
        $skippedCount = 0;

        foreach ($candidates as $candidate) {
            if ($candidate->approval_status === 'approved') {
                $skippedCount++;
                continue;
            }

            if (! $candidate->email) {
                $candidate->update([
                    'email_status' => 'failed',
                    'email_error' => 'Candidate does not have an email address.',
                ]);

                $skippedCount++;
                continue;
            }

            $approvedCandidate = $this->approveCandidate(
                candidate: $candidate,
                template: $template,
                approvalSource: 'bulk',
                approvalNote: $validated['approval_note'] ?? null
            );

            SendCandidateApprovalEmail::dispatch($approvedCandidate->id);

            $approvedCount++;
            $queuedEmailCount++;
        }

        return back()->with(
            'success',
            "{$approvedCount} candidate(s) approved. {$queuedEmailCount} email(s) queued. {$skippedCount} candidate(s) skipped."
        );
    }

    private function approveCandidate(
        Candidate $candidate,
        EmailTemplate $template,
        string $approvalSource,
        ?string $approvalNote
    ): ApprovedCandidate {
        return DB::transaction(function () use ($candidate, $template, $approvalSource, $approvalNote) {
            $approvedAt = now();

            $candidate->update([
                'approval_status' => 'approved',
                'approved_at' => $approvedAt,
                'approved_by' => Auth::id(),
                'approval_note' => $approvalNote,
                'approval_source' => $approvalSource,
                'email_status' => 'queued',
                'email_error' => null,
                'email_template_id' => $template->id,
            ]);

            return ApprovedCandidate::updateOrCreate(
                [
                    'candidate_id' => $candidate->id,
                ],
                [
                    'full_name_snapshot' => $candidate->full_name,
                    'email_snapshot' => $candidate->email,
                    'candidate_category_snapshot' => $candidate->candidate_category,
                    'leaderboard_rank_snapshot' => $candidate->leaderboard_rank,
                    'leaderboard_score_snapshot' => $candidate->leaderboard_score,
                    'match_percentage_snapshot' => $candidate->match_percentage,
                    'approved_by' => Auth::id(),
                    'approved_at' => $approvedAt,
                    'approval_note' => $approvalNote,
                    'approval_source' => $approvalSource,
                    'email_template_id' => $template->id,
                    'email_status' => 'queued',
                    'email_sent_at' => null,
                    'email_error' => null,
                ]
            );
        });
    }
}
