<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\NewsCategories;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'category_id',
        'slug',
        'title',
        'content',
        'img_cover',
        'status',
        'is_highlight',
        'created_by',
        'updated_by',
    ];

    public function category()
    {
        return $this->belongsTo(NewsCategories::class, 'category_id');
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