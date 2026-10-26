<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VotingDpt extends Model
{
    protected $table = 'voting_dpt';

    protected $fillable = [
        'voting_id', 'user_id', 'fullname', 'username', 'role_id',
        'tahun_masuk', 'masa_studi', 'voted_at',
    ];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function voting()
    {
        return $this->belongsTo(Voting::class, 'voting_id');
    }
}
