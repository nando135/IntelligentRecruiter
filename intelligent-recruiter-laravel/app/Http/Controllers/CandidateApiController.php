<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\JsonResponse;

class CandidateApiController extends Controller
{
    public function index(): JsonResponse
    {
        $candidates = Candidate::withCount(['experiences', 'educations', 'skills'])
            ->latest()
            ->get()
            ->map(fn($c) => [
                'id'                        => $c->id,
                'full_name'                 => $c->full_name,
                'email'                     => $c->email,
                'phone'                     => $c->phone,
                'current_job_title'         => $c->current_job_title,
                'latest_company'            => $c->latest_company,
                'total_experience_years'    => $c->total_experience_years,
                'parser_status'             => $c->parser_status,
                'parser_warning'            => $c->parser_warning,
                'candidate_category'        => $c->candidate_category,
                'classification_confidence' => $c->classification_confidence,
                'classification_reason'     => $c->classification_reason,
                'leaderboard_rank'          => $c->leaderboard_rank,
                'leaderboard_score'         => $c->leaderboard_score,
                'match_percentage'          => $c->match_percentage,
                'ranking_reason'            => $c->ranking_reason,
                'ranked_at'                 => $c->ranked_at,
                'experiences_count'         => $c->experiences_count,
                'educations_count'          => $c->educations_count,
                'skills_count'              => $c->skills_count,
                'created_at'               => $c->created_at,
            ]);

        return response()->json([
            'status' => 'success',
            'total'  => $candidates->count(),
            'data'   => $candidates,
        ]);
    }

    public function show(Candidate $candidate): JsonResponse
    {
        $candidate->load([
            'experiences',
            'educations',
            'skills',
            'projects',
            'certifications',
            'achievements',
            'languages',
            'links',
        ]);

        return response()->json([
            'status'    => 'success',
            'candidate' => $candidate,
        ]);
    }

    public function parsedJson(Candidate $candidate): JsonResponse
    {
        return response()->json([
            'status'        => 'success',
            'candidate_id'  => $candidate->id,
            'parser_status' => $candidate->parser_status,
            'parser_warning'=> $candidate->parser_warning,
            'parsed_json'   => $candidate->parsed_json,
        ]);
    }

    public function rawText(Candidate $candidate): JsonResponse
    {
        return response()->json([
            'status'      => 'success',
            'candidate_id'=> $candidate->id,
            'raw_text'    => $candidate->raw_text,
        ]);
    }

    public function experiences(Candidate $candidate): JsonResponse
    {
        return response()->json([
            'status'      => 'success',
            'candidate_id'=> $candidate->id,
            'total'       => $candidate->experiences()->count(),
            'data'        => $candidate->experiences,
        ]);
    }

    public function skills(Candidate $candidate): JsonResponse
    {
        $grouped = $candidate->skills()
            ->get()
            ->groupBy('category')
            ->map(fn($group) => $group->pluck('skill'));

        return response()->json([
            'status'      => 'success',
            'candidate_id'=> $candidate->id,
            'data'        => $grouped,
        ]);
    }
}
