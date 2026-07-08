<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbacksCategories extends Model
{
    protected $table = 'feedbacks_categories';

    protected $fillable = [
        'category_name',
        'status',
        'created_by',
        'updated_by'
    ];
    
    protected $casts = [
        'status' => 'boolean',
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