<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'status_code',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'status_code' => 'boolean'
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
