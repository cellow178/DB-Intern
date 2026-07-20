<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MajorCompetent extends Model
{
    protected $table = 'major_competent';

    protected $fillable = [
        'major_id',
        'competent_name',
        'description',
        'active',
        'created_by',
        'updated_by'
    ];

    public function major()
    {
        return $this->belongsTo(Major::class);
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
