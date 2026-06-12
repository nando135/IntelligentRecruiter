<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CandidateRankingService
{
    public function rerankCategory(?string $category): void
    {
        $category = trim((string) $category);

        if ($category === '') {
            return;
        }

        $candidates = Candidate::with('skills')
            ->where('candidate_category', $category)
            ->get();

        if ($candidates->isEmpty()) {
            return;
        }

        $payload = [
            'category' => $category,
            'candidates' => $candidates->map(function (Candidate $candidate) {
                return [
                    'id' => $candidate->id,
                    'full_name' => $candidate->full_name,
                    'category' => $candidate->candidate_category,
                    'current_job_title' => $candidate->current_job_title,
                    'professional_summary' => $candidate->professional_summary,
                    'total_experience_years' => (float) ($candidate->total_experience_years ?? 0),
                    'skills' => $candidate->skills
                        ->pluck('skill')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })->values()->all(),
        ];

        $pythonUrl = rtrim(env('PYTHON_AI_URL', 'http://127.0.0.1:8001'), '/');

        try {
            $response = Http::timeout(180)->post($pythonUrl . '/rank-candidates', $payload);
        } catch (\Throwable $e) {
            Log::warning('Candidate ranking skipped because Python AI service is unreachable.', [
                'category' => $category,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (! $response->successful()) {
            Log::warning('Candidate ranking failed.', [
                'category' => $category,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return;
        }

        $rankings = $response->json('rankings', []);

        foreach ($rankings as $ranking) {
            $candidateId = $ranking['candidate_id'] ?? null;

            if (! $candidateId) {
                continue;
            }

            Candidate::whereKey($candidateId)->update([
                'leaderboard_rank' => $ranking['rank'] ?? null,
                'leaderboard_score' => $ranking['score'] ?? null,
                'match_percentage' => $ranking['match_percentage'] ?? null,
                'ranking_reason' => $ranking['reason'] ?? null,
                'ranked_at' => now(),
            ]);
        }
    }

    public function rankByJobDescription(string $jobDescription, $candidates): array
    {
        $jobDescription = trim($jobDescription);

        if ($jobDescription === '') {
            return [
                'status' => 'error',
                'message' => 'Job description is required.',
                'job_requirements' => [],
                'rankings' => [],
            ];
        }

        $payload = [
            'job_description' => $jobDescription,
            'candidates' => $candidates->map(function (Candidate $candidate) {
                return $this->mapCandidateForJobRanking($candidate);
            })->values()->all(),
        ];

        $pythonUrl = rtrim(env('PYTHON_AI_URL', 'http://127.0.0.1:8001'), '/');

        try {
            $response = Http::timeout(180)->post($pythonUrl . '/rank-candidates-by-job', $payload);
        } catch (\Throwable $e) {
            Log::warning('JD-based candidate ranking skipped because Python AI service is unreachable.', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Python AI service is unreachable. Make sure FastAPI is running on ' . $pythonUrl,
                'job_requirements' => [],
                'rankings' => [],
            ];
        }

        if (! $response->successful()) {
            Log::warning('JD-based candidate ranking failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'status' => 'error',
                'message' => 'JD-based ranking failed. Check Laravel logs and FastAPI logs.',
                'job_requirements' => [],
                'rankings' => [],
            ];
        }

        return [
            'status' => $response->json('status', 'success'),
            'message' => $response->json('message'),
            'job_requirements' => $response->json('job_requirements', []),
            'rankings' => $response->json('rankings', []),
        ];
    }

    private function mapCandidateForJobRanking(Candidate $candidate): array
    {
        return [
            'id' => $candidate->id,
            'full_name' => $candidate->full_name,
            'category' => $candidate->candidate_category,
            'current_job_title' => $candidate->current_job_title,
            'professional_summary' => $candidate->professional_summary,
            'total_experience_years' => (float) ($candidate->total_experience_years ?? 0),

            'skills' => $candidate->skills
                ->pluck('skill')
                ->filter()
                ->unique()
                ->values()
                ->all(),

            'experiences' => $candidate->experiences->map(function ($experience) {
                return [
                    'company_name' => $experience->company_name,
                    'job_title' => $experience->job_title,
                    'employment_type' => $experience->employment_type,
                    'department' => $experience->department,
                    'responsibilities' => $experience->responsibilities ?? [],
                    'achievements' => $experience->achievements ?? [],
                    'tools_used' => $experience->tools_used ?? [],
                    'industry' => $experience->industry,
                ];
            })->values()->all(),

            'projects' => $candidate->projects->map(function ($project) {
                return [
                    'project_name' => $project->project_name,
                    'project_type' => $project->project_type,
                    'description' => $project->description,
                    'technologies' => $project->technologies ?? [],
                    'role' => $project->role,
                    'outcome' => $project->outcome,
                ];
            })->values()->all(),

            'educations' => $candidate->educations->map(function ($education) {
                return [
                    'institution' => $education->institution,
                    'degree' => $education->degree,
                    'field_of_study' => $education->field_of_study,
                    'relevant_coursework' => $education->relevant_coursework ?? [],
                ];
            })->values()->all(),

            'certifications' => $candidate->certifications->map(function ($certification) {
                return [
                    'name' => $certification->name,
                    'issuer' => $certification->issuer,
                    'date_issued' => $certification->date_issued,
                ];
            })->values()->all(),

            'raw_text' => mb_substr((string) $candidate->raw_text, 0, 6000),
        ];
    }
}
