<?php

namespace App\Models;

use App\CoreService\CallService;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class MajorGallery extends Model
{
    protected $table = 'major_gallery';
    protected $dateFormat = 'c';
    const TABLE = "major_gallery";
    const FILEROOT = "/major_gallery";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;
    const FIELD_LIST = ["id", "major_id", "img_cover", "description", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_ADD = ["major_id", "img_cover", "description", "active", "created_by", "updated_by"];
    const FIELD_EDIT = ["major_id", "img_cover", "description", "active", "updated_by"];
    const FIELD_VIEW = ["id", "major_id", "img_cover", "description", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_READONLY = [];
    const FIELD_FILTERABLE = [
        "id" => [
            "operator" => "=",
        ],
        "major_id" => [
            "operator" => "=",
        ],
        "img_cover" => [
            "operator" => "=",
        ],
        "description" => [
            "operator" => "=",
        ],
        "active" => [
            "operator" => "=",
        ],
        "created_by" => [
            "operator" => "=",
        ],
        "updated_by" => [
            "operator" => "=",
        ],
        "created_at" => [
            "operator" => "=",
        ],
        "updated_at" => [
            "operator" => "=",
        ],
    ];
    const FIELD_SEARCHABLE = ["description"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = ["id", "major_id", "img_cover", "description", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_UNIQUE = [];
    const FIELD_UPLOAD = ["img_cover"];
    const FIELD_TYPE = [
        "id" => "bigint",
        "major_id" => "bigint",
        "img_cover" => "text",
        "description" => "character_varying",
        "active" => "boolean",
        "created_by" => "bigint",
        "updated_by" => "bigint",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [
        "major_id" => "",
        "img_cover" => "",
        "description" => "",
        "active" => "true",
        "created_by" => "",
        "updated_by" => "",
        "created_at" => "",
        "updated_at" => "",
    ];
    const FIELD_RELATION = [
        "major_id" => [
            "linkTable" => "majors",
            "aliasTable" => "B",
            "linkField" => "id",
            "displayName" => "rel_major_id",
            "selectFields" => ["id", "code", "major_name"],
            "selectValue" => "id AS rel_major_id, B.code AS rel_major_code, B.major_name AS rel_major_name"
        ],
        "created_by" => [
            "linkTable" => "users",
            "aliasTable" => "C",
            "linkField" => "id",
            "displayName" => "rel_created_by",
            "selectFields" => ["username"],
            "selectValue" => "id AS rel_created_by"
        ],
        "updated_by" => [
            "linkTable" => "users",
            "aliasTable" => "D",
            "linkField" => "id",
            "displayName" => "rel_updated_by",
            "selectFields" => ["username"],
            "selectValue" => "id AS rel_updated_by"
        ],
    ];
    const CUSTOM_RELATION = [];
    const CUSTOM_SELECT = "";
    const FIELD_VALIDATION = [
        "major_id" => "required|integer",
        "img_cover" => "required|string|exists_file",
        "description" => "required|string|max:255",
        "active" => "required",
        "created_by" => "nullable|integer",
        "updated_by" => "nullable|integer",
        "created_at" => "nullable|date",
        "updated_at" => "nullable|date",
    ];
    const PARENT_CHILD = [];
    // start custom
    const CUSTOM_LIST_FILTER = [];
    const FIELD_CASTING = [
        //"nama field" => "float",
    ];
    const FIELD_VALIDATION_DATA = [];
    const CHILD_TABLE = [
        //"child_table" => [
        // "foreignField" => "field"
        //]
    ];
    const MAPPING_MULTIPLE_ADD = [
        //"contracts" => [ -- main table (contract_id)
        //    "dataIdTable" => "m_poc", -- data (mapping id)
        //    "fieldAdd" => [],
        //    "fieldUnique" => [],
        //],
    ];

    public function major()
    {
        return $this->belongsTo(Majors::class, 'major_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function beforeInsert(array $input): array
    {
        return $input;
    }

    public static function afterInsert(mixed $object, array $input): array
    {
        return $input;
    }

    public static function beforeUpdate(array $input): array
    {
        return $input;
    }

    public static function afterUpdate(mixed $object, array $input): array
    {
        return $input;
    }

    public static function beforeDelete(array $input): array
    {
        return $input;
    }

    public static function afterDelete(mixed $object, array $input): array
    {
        return $input;
    }
    // end custom
}
