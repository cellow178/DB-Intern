<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VotingCandidate extends Model
{
    protected $table = 'voting_candidates';

    protected $fillable = [
        'voting_id',
        'img_cover',
        'title',
        'description',
        'order',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function voting()
    {
        return $this->belongsTo(Voting::class, 'voting_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}