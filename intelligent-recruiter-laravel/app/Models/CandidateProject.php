<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateProject extends Model
{
    protected $fillable = [
        'candidate_id',
        'project_name',
        'project_type',
        'description',
        'technologies',
        'role',
        'outcome',
    ];

    protected $casts = [
        'technologies' => 'array',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
