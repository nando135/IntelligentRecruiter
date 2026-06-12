<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateAchievement extends Model
{
    protected $fillable = [
        'candidate_id',
        'title',
        'description',
        'year',
        'organization',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
