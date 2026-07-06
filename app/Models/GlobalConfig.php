<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalConfig extends Model
{
    protected $table = 'global_config';

    protected $fillable = [
        'profile_title',
        'profile_description',
        'img_profile_1',
        'img_profile_2',
        'school_vision',
        'video_profile',
        'school_name',
        'footer_description',
        'motto',
        'school_telephone',
        'school_email',
        'footer_ig',
        'footer_yt',
        'footer_fb',
        'footer_linkedin',
        'created_by',
        'updated_by'
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