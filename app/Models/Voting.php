<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voting extends Model
{
    protected $table = 'votings';

    protected $fillable = [
        'slug',
        'img_cover',
        'title',
        'description',
        'start_date',
        'end_date',
        'active',
        'is_highlight',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'start_date'   => 'datetime',
        'end_date'     => 'datetime',
        'active'       => 'boolean',
        'is_highlight' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function votingCandidate(): HasMany
    {
        return $this->hasMany(VotingCandidate::class, 'voting_id');
    }    
}
