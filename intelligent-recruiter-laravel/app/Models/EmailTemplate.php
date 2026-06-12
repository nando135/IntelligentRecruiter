<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'name',
        'subject',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function approvedCandidates()
    {
        return $this->hasMany(ApprovedCandidate::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public static function getActiveOrDefault(): self
    {
        $activeTemplate = self::where('is_active', true)->latest()->first();

        if ($activeTemplate) {
            return $activeTemplate;
        }

        $template = self::firstOrCreate(
            ['name' => 'Default Approval Template'],
            [
                'subject' => 'Application Update - Next Stage Confirmation',
                'body' => "Dear {{candidate_name}},\n\nCongratulations. You have been selected to proceed to the next stage of our recruitment process.\n\nYour application has been reviewed and approved by our HR team.\n\nCategory: {{candidate_category}}\nRank: {{leaderboard_rank}}\nMatch Score: {{match_percentage}}%\n\nOur team will contact you with the next steps.\n\nBest regards,\nHR Team",
                'is_active' => true,
            ]
        );

        if (! $template->is_active) {
            $template->update(['is_active' => true]);
        }

        return $template;
    }

    public function renderSubject(Candidate $candidate): string
    {
        return $this->replaceVariables($this->subject, $candidate);
    }

    public function renderBody(Candidate $candidate): string
    {
        return $this->replaceVariables($this->body, $candidate);
    }

    protected function replaceVariables(string $content, Candidate $candidate): string
    {
        $variables = [
            'candidate_name'       => $candidate->full_name ?? 'Candidate',
            'candidate_email'      => $candidate->email ?? '-',
            'candidate_category'   => $candidate->candidate_category ?? '-',
            'leaderboard_rank'     => $candidate->leaderboard_rank ? '#' . $candidate->leaderboard_rank : '-',
            'leaderboard_score'    => $candidate->leaderboard_score !== null ? number_format((float) $candidate->leaderboard_score, 2) : '-',
            'match_percentage'     => $candidate->match_percentage !== null ? number_format((float) $candidate->match_percentage, 2) : '-',
            'current_job_title'    => $candidate->current_job_title ?? '-',
            'latest_company'       => $candidate->latest_company ?? '-',
            'company_name'         => config('app.name', 'Intelligent Recruiter'),
        ];

        return preg_replace_callback('/{{\s*([a-zA-Z0-9_]+)\s*}}/', function ($matches) use ($variables) {
            return $variables[$matches[1]] ?? $matches[0];
        }, $content);
    }
}
