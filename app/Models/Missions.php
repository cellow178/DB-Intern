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


class Missions extends Model
{
    protected $table = 'missions';
    protected $dateFormat = 'c';
    protected $fillable = [
        'content',
        'order',
        'active',
        'created_by',
        'updated_by',
    ];
    const TABLE = "missions";
    const FILEROOT = "/missions";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;
    const FIELD_LIST = ["id", "content", "order", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_ADD = ["content", "order", "active", "created_by", "updated_by"];
    const FIELD_EDIT = ["content", "order", "active", "updated_by"];
    const FIELD_VIEW = ["id", "content", "order", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_READONLY = [];
    const FIELD_FILTERABLE = [
        "id" => [
            "operator" => "=",
        ],
        "content" => [
            "operator" => "=",
        ],
        "order" => [
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
    const FIELD_SEARCHABLE = ["content"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = ["id", "content", "order", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_UNIQUE = [["order"]];
    const FIELD_UPLOAD = [];
    const FIELD_TYPE = [
        "id" => "bigint",
        "content" => "character_varying",
        "order" => "integer",
        "active" => "boolean",
        "created_by" => "bigint",
        "updated_by" => "bigint",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [
        "content" => "",
        "order" => "",
        "active" => "true",
        "created_by" => "",
        "updated_by" => "",
        "created_at" => "",
        "updated_at" => "",
    ];
    const FIELD_RELATION = [
        "created_by" => [
            "linkTable" => "users",
            "aliasTable" => "B",
            "linkField" => "id",
            "displayName" => "rel_created_by",
            "selectFields" => ["username"],
            "selectValue" => "id AS rel_created_by"
        ],
        "updated_by" => [
            "linkTable" => "users",
            "aliasTable" => "C",
            "linkField" => "id",
            "displayName" => "rel_updated_by",
            "selectFields" => ["username"],
            "selectValue" => "id AS rel_updated_by"
        ],
    ];
    const CUSTOM_RELATION = [];
    const CUSTOM_SELECT = "";
    const FIELD_VALIDATION = [
        "content"    => "required|string|max:255",
        "order"      => "required|integer",
        "active"     => "nullable|boolean",
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

    public static function afterUpdate($object, array $input)
    {
        return $input;
    }

    public static function beforeDelete(array $input)
    {
        return $input;
    }

    public static function afterDelete($object, array $input)
    {
        return $input;
    } // end custom
}
