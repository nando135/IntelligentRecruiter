<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateExperience extends Model
{
    protected $fillable = [
        'candidate_id',
        'company_name',
        'job_title',
        'employment_type',
        'department',
        'location',
        'start_date',
        'end_date',
        'is_current',
        'duration_months',
        'responsibilities',
        'achievements',
        'tools_used',
        'industry',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'responsibilities' => 'array',
        'achievements' => 'array',
        'tools_used' => 'array',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
