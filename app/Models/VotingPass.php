<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VotingPass extends Model
{
    protected $table = 'voting_passes';

    protected $fillable = [
        'voting_id', 'user_id',
        'pass_hash', 'pass_expired_at', 'pass_used_at',
        'ballot_token_hash', 'ballot_expired_at', 'ballot_used_at',
        'created_by',
    ];

    protected $casts = [
        'pass_expired_at' => 'datetime',
        'pass_used_at' => 'datetime',
        'ballot_expired_at' => 'datetime',
        'ballot_used_at' => 'datetime',
    ];
}
