<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackCategory extends Model
{
    protected $table = 'feedbacks_categories';

    protected $fillable = [
        'category_name',
        'active',
        'created_by',
        'updated_by'
    ];
    
    protected $casts = [
        'active' => 'boolean',
    ];

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'category_id');
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