<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateEducation extends Model
{
    protected $table = 'candidate_educations';

    protected $fillable = [
        'candidate_id',
        'institution',
        'degree',
        'field_of_study',
        'start_year',
        'end_year',
        'cgpa',
        'relevant_coursework',
    ];

    protected $casts = [
        'relevant_coursework' => 'array',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
