<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateCertification extends Model
{
    protected $fillable = [
        'candidate_id',
        'name',
        'issuer',
        'date_issued',
        'expiry_date',
        'credential_link',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
