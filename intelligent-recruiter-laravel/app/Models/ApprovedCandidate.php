<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovedCandidate extends Model
{
    protected $fillable = [
        'candidate_id',
        'full_name_snapshot',
        'email_snapshot',
        'candidate_category_snapshot',
        'leaderboard_rank_snapshot',
        'leaderboard_score_snapshot',
        'match_percentage_snapshot',
        'approved_by',
        'approved_at',
        'approval_note',
        'approval_source',
        'email_template_id',
        'email_status',
        'email_sent_at',
        'email_error',
    ];

    protected $casts = [
        'approved_at'                 => 'datetime',
        'email_sent_at'               => 'datetime',
        'leaderboard_score_snapshot'  => 'decimal:2',
        'match_percentage_snapshot'   => 'decimal:2',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }
}
