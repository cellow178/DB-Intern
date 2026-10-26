<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VotingBallot extends Model
{
    protected $table = 'voting_ballots';

    protected $fillable = [
        'voting_id', 'candidate_id',
    ];

    public function candidate()
    {
        return $this->belongsTo(VotingCandidate::class, 'candidate_id');
    }
}
