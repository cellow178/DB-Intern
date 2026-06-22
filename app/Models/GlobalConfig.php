<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalConfig extends Model
{
    protected $table = 'global_config'; // Menentukan nama tabel karena bukan jamak (plural)

    protected $fillable = [
        'profile_title', 'profile_description', 'img_profile_1', 'img_profile_2',
        'school_vission', 'video_profile', 'school-name', 'footer_description',
        'motto', 'school_telephone', 'school_email', 'footer_ig', 'footer_yt',
        'footer_fb', 'footer_linkedin', 'created_by', 'updated_by'
    ];
}