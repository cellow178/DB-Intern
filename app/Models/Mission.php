<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mission extends Model
{
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected $fillable = [
        'content', 'order', 'status_code', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'status_code' => 'boolean', // Otomatis mengubah 0/1 menjadi false/true
    ];
}