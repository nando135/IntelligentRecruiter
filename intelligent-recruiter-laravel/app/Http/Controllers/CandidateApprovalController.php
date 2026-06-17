<?php

namespace App\Http\Controllers;

use App\Models\ApprovedCandidate;
use App\Models\Candidate;
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

        $this->approveCandidate(
            candidate: $candidate,
            approvalSource: $validated['approval_source'] ?? 'single',
            approvalNote: $validated['approval_note'] ?? null
        );

        return back()->with('success', 'Candidate approved successfully.');
    }

    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'candidate_ids' => ['required', 'array', 'min:1'],
            'candidate_ids.*' => ['integer', 'exists:candidates,id'],
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $candidates = Candidate::whereIn('id', $validated['candidate_ids'])
            ->where('user_id', Auth::id())
            ->get();

        $approvedCount = 0;
        $skippedCount = 0;

        foreach ($candidates as $candidate) {
            if ($candidate->approval_status === 'approved') {
                $skippedCount++;
                continue;
            }

            $this->approveCandidate(
                candidate: $candidate,
                approvalSource: 'bulk',
                approvalNote: $validated['approval_note'] ?? null
            );

            $approvedCount++;
        }

        return back()->with(
            'success',
            "{$approvedCount} candidate(s) approved. {$skippedCount} candidate(s) skipped."
        );
    }

    private function approveCandidate(
        Candidate $candidate,
        string $approvalSource,
        ?string $approvalNote
    ): ApprovedCandidate {
        return DB::transaction(function () use ($candidate, $approvalSource, $approvalNote) {
            $approvedAt = now();

            $candidate->update([
                'approval_status' => 'approved',
                'approved_at' => $approvedAt,
                'approved_by' => Auth::id(),
                'approval_note' => $approvalNote,
                'approval_source' => $approvalSource,
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
                ]
            );
        });
    }
}
