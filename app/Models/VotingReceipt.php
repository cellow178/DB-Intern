<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VotingReceipt extends Model
{
    protected $table = 'voting_receipts';

    protected $fillable = [
        'voting_id', 'user_id', 'voted_at', 'ip', 'user_agent',
    ];

    protected $casts = [
        'voted_at' => 'datetime',
    ];
}
