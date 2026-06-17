<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Services\CandidateRankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->buildLeaderboardData($request);

        $data['jobDescription'] = '';
        $data['jobRanking'] = null;

        return view('leaderboard.index', $data);
    }

    public function rankByJob(Request $request, CandidateRankingService $rankingService)
    {
        $validated = $request->validate([
            'job_description' => ['required', 'string', 'min:20', 'max:10000'],
        ], [
            'job_description.required' => 'Please paste a job description first.',
            'job_description.min' => 'The job description is too short. Please paste a more complete job description.',
        ]);

        $jobDescription = $validated['job_description'];

        $data = $this->buildLeaderboardData($request);

        $candidatesForRanking = Candidate::with([
                'skills',
                'experiences',
                'projects',
                'educations',
                'certifications',
            ])
            ->where('user_id', Auth::id())
            ->where(function ($query) {
                $query->whereNull('approval_status')
                    ->orWhere('approval_status', '!=', 'approved');
            })
            ->get();

        if ($candidatesForRanking->isEmpty()) {
            $data['jobDescription'] = $jobDescription;
            $data['jobRanking'] = [
                'status' => 'error',
                'message' => 'No pending candidates are available for JD-based ranking.',
                'job_requirements' => [],
                'rankings' => [],
            ];

            return view('leaderboard.index', $data);
        }

        $data['jobDescription'] = $jobDescription;
        $data['jobRanking'] = $rankingService->rankByJobDescription(
            $jobDescription,
            $candidatesForRanking
        );

        return view('leaderboard.index', $data);
    }

    private function buildLeaderboardData(Request $request): array
    {
        $categories = ['IT', 'Business', 'Data', 'Marketing', 'Finance', 'Operations'];
        $selectedCategory = $request->query('category');
        $hideApproved = $request->boolean('hide_approved');

        if ($selectedCategory && ! in_array($selectedCategory, $categories, true)) {
            $selectedCategory = null;
        }

        $query = Candidate::with('skills')
            ->where('user_id', Auth::id())
            ->whereNotNull('candidate_category')
            ->where('candidate_category', '!=', '')
            ->orderBy('candidate_category')
            ->orderByRaw('leaderboard_rank IS NULL, leaderboard_rank ASC')
            ->orderByDesc('leaderboard_score')
            ->orderByDesc('match_percentage')
            ->orderByDesc('total_experience_years');

        if ($selectedCategory) {
            $query->where('candidate_category', $selectedCategory);
        }

        if ($hideApproved) {
            $query->where(function ($q) {
                $q->whereNull('approval_status')
                    ->orWhere('approval_status', '!=', 'approved');
            });
        }

        $candidates = $query->get();
        $groupedCandidates = $candidates->groupBy('candidate_category');

        return compact(
            'categories',
            'selectedCategory',
            'hideApproved',
            'groupedCandidates'
        );
    }
}
