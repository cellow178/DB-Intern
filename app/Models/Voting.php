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
        'mode',
        'include_siswa',
        'include_teachers',
        'masa_studi_list',
        'tahun_masuk_list',
        'include_tingkat_13',
        'dpt_total',
        'dpt_frozen_at',
        'closed_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'start_date'   => 'datetime',
        'end_date'     => 'datetime',
        'active'       => 'boolean',
        'is_highlight' => 'boolean',
        'include_siswa' => 'boolean',
        'include_teachers' => 'boolean',
        'masa_studi_list' => 'array',
        'tahun_masuk_list' => 'array',
        'include_tingkat_13' => 'boolean',
        'dpt_frozen_at' => 'datetime',
        'closed_at' => 'datetime',
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
