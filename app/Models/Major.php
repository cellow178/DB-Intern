<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Major extends Model
{
    protected $table = 'majors';

    protected $fillable = [
        'slug',
        'img_logo',
        'code',
        'major_name',
        'summary',
        'total_classes',
        'major_duration',
        'full_description',
        'active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function competent(): HasMany
    {
        return $this->hasMany(MajorCompetent::class, 'major_id');
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
