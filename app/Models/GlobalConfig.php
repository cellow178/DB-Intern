<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GlobalConfig extends Model
{
    protected $table = 'global_config';
    protected $dateFormat = 'c';

    protected $fillable = [
        'hero_description',
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

    const TABLE = "global_config";
    const FILEROOT = "/global_config";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = false;
    const IS_VIEW = true;

    const FIELD_LIST = [
        "id",
        "hero_description",
        "profile_title",
        "profile_description",
        "img_profile_1",
        "img_profile_2",
        "school_vision",
        "video_profile",
        "school_name",
        "footer_description",
        "motto",
        "school_telephone",
        "school_email",
        "footer_ig",
        "footer_yt",
        "footer_fb",
        "footer_linkedin",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at"
    ];

    const FIELD_ADD = [
        "hero_description",
        "profile_title",
        "profile_description",
        "img_profile_1",
        "img_profile_2",
        "school_vision",
        "video_profile",
        "school_name",
        "footer_description",
        "motto",
        "school_telephone",
        "school_email",
        "footer_ig",
        "footer_yt",
        "footer_fb",
        "footer_linkedin",
        "created_by",
        "updated_by"
    ];

    const FIELD_EDIT = [
        "hero_description",
        "profile_title",
        "profile_description",
        "img_profile_1",
        "img_profile_2",
        "school_vision",
        "video_profile",
        "school_name",
        "footer_description",
        "motto",
        "school_telephone",
        "school_email",
        "footer_ig",
        "footer_yt",
        "footer_fb",
        "footer_linkedin",
        "updated_by"
    ];

    const FIELD_VIEW = [
        "id",
        "hero_description",
        "profile_title",
        "profile_description",
        "img_profile_1",
        "img_profile_2",
        "school_vision",
        "video_profile",
        "school_name",
        "footer_description",
        "motto",
        "school_telephone",
        "school_email",
        "footer_ig",
        "footer_yt",
        "footer_fb",
        "footer_linkedin",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at"
    ];

    const FIELD_READONLY = [];
    const FIELD_FILTERABLE = [
        "id" => ["operator" => "="],
    ];
    const FIELD_SEARCHABLE = ["school_name"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = ["id", "created_at", "updated_at"];
    const FIELD_UNIQUE = [];

    // Mendaftarkan kolom upload file agar diproses otomatis oleh CallService
    const FIELD_UPLOAD = ["img_profile_1", "img_profile_2"];

    const FIELD_TYPE = [
        "id"                  => "bigint",
        "hero_description"    => "text",
        "profile_title"       => "character_varying",
        "profile_description" => "text",
        "img_profile_1"       => "text",
        "img_profile_2"       => "text",
        "school_vision"       => "text",
        "video_profile"       => "text",
        "school_name"         => "character_varying",
        "footer_description"  => "text",
        "motto"               => "character_varying",
        "school_telephone"    => "character_varying",
        "school_email"        => "character_varying",
        "footer_ig"           => "text",
        "footer_yt"           => "text",
        "footer_fb"           => "text",
        "footer_linkedin"     => "text",
        "created_by"          => "bigint",
        "updated_by"          => "bigint",
        "created_at"          => "timestamp_with_time_zone",
        "updated_at"          => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [];

    const FIELD_RELATION = [
        "created_by" => [
            "linkTable"    => "users",
            "aliasTable"   => "B",
            "linkField"    => "id",
            "displayName"  => "rel_created_by",
            "selectFields" => ["username"],
            "selectValue"  => "id AS rel_created_by"
        ],
        "updated_by" => [
            "linkTable"    => "users",
            "aliasTable"   => "C",
            "linkField"    => "id",
            "displayName"  => "rel_updated_by",
            "selectFields" => ["username"],
            "selectValue"  => "id AS rel_updated_by"
        ],
    ];

    const CUSTOM_RELATION = [];
    const CUSTOM_SELECT = "";

    const FIELD_VALIDATION = [
        "hero_description"    => "required|string|max:100",
        "profile_title"       => "required|string",
        "profile_description" => "required|string",
        "img_profile_1"       => "required|string|exists_file",
        "img_profile_2"       => "nullable",
        "school_vision"       => "required|string",
        "video_profile"       => "required|string",
        "school_name"         => "required|string|max:150",
        "footer_description"  => "nullable|string",
        "motto"               => "required|string|max:100",
        "school_telephone"    => "required|string|max:150",
        "school_email"        => "required|email",
        "footer_ig"           => "nullable|string",
        "footer_yt"           => "nullable|string",
        "footer_fb"           => "nullable|string",
        "footer_linkedin"     => "nullable|string",
    ];

    const PARENT_CHILD = [];
    const CUSTOM_LIST_FILTER = [];
    const FIELD_CASTING = [];
    const FIELD_VALIDATION_DATA = [];
    const CHILD_TABLE = [];
    const MAPPING_MULTIPLE_ADD = [];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function beforeInsert(array $input)
    {
        return $input;
    }
    public static function afterInsert($object, array $input)
    {
        return $input;
    }
    public static function beforeUpdate(array $input)
    {
        return $input;
    }

    public static function afterUpdate(mixed $object, array $input)
    {
        if (array_key_exists('img_profile_2', $input) && empty($input['img_profile_2'])) {
            self::where('id', $object->id)->update([
                'img_profile_2' => null
            ]);
        }

        return $input;
    }
    public static function beforeDelete(array $input)
    {
        return $input;
    }
    public static function afterDelete($object, array $input)
    {
        return $input;
    }
}
