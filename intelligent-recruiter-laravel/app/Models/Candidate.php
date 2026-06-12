<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'location',
        'current_job_title',
        'latest_company',
        'professional_summary',
        'total_experience_months',
        'total_experience_years',
        'internship_experience_months',
        'full_time_experience_months',
        'resume_file',
        'raw_text',
        'parsed_json',
        'parser_status',
        'parser_warning',
        'source_filename',
        'file_hash',
        'candidate_category',
        'classification_confidence',
        'classification_reason',
        'leaderboard_rank',
        'leaderboard_score',
        'match_percentage',
        'ranking_reason',
        'ranked_at',

        'approval_status',
        'approved_at',
        'approved_by',
        'approval_note',
        'approval_source',
        'email_sent_at',
        'email_status',
        'email_error',
        'email_template_id',
    ];

    protected $casts = [
        'parsed_json'               => 'array',
        'total_experience_years'    => 'decimal:2',
        'classification_confidence' => 'decimal:2',
        'leaderboard_score'         => 'decimal:2',
        'match_percentage'          => 'decimal:2',
        'ranked_at'                 => 'datetime',
        'approved_at'               => 'datetime',
        'email_sent_at'             => 'datetime',
    ];

    public function experiences()
    {
        return $this->hasMany(CandidateExperience::class);
    }

    public function educations()
    {
        return $this->hasMany(CandidateEducation::class);
    }

    public function skills()
    {
        return $this->hasMany(CandidateSkill::class);
    }

    public function projects()
    {
        return $this->hasMany(CandidateProject::class);
    }

    public function certifications()
    {
        return $this->hasMany(CandidateCertification::class);
    }

    public function achievements()
    {
        return $this->hasMany(CandidateAchievement::class);
    }

    public function languages()
    {
        return $this->hasMany(CandidateLanguage::class);
    }

    public function links()
    {
        return $this->hasOne(CandidateLink::class);
    }

    public function approvedCandidate()
    {
        return $this->hasOne(ApprovedCandidate::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public function approvalEmailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }
}
