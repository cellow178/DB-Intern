<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    protected $fillable = [
        'content', 'order', 'status_code', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'status_code' => 'boolean', // Otomatis mengubah 0/1 menjadi false/true
    ];
}