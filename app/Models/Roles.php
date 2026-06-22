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


class Roles extends Model
{
    protected $table = 'roles';
    protected $dateFormat = 'c';
    const TABLE = "roles";
    const FILEROOT = "/roles";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;
    const FIELD_LIST = ["id", "role_code", "role_name", "role_group_id", "description", "allow_login", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_ADD = ["role_code", "role_name", "role_group_id", "description", "allow_login", "active", "created_by", "updated_by"];
    const FIELD_EDIT = ["role_code", "role_name", "role_group_id", "description", "allow_login", "active", "updated_by"];
    const FIELD_VIEW = ["id", "role_code", "role_name", "role_group_id", "description", "allow_login", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_READONLY = [];
    const FIELD_FILTERABLE = [
        "id" => [
            "operator" => "=",
        ],
        "role_code" => [
            "operator" => "=",
        ],
        "role_name" => [
            "operator" => "=",
        ],
        "role_group_id" => [
            "operator" => "=",
        ],
        "description" => [
            "operator" => "=",
        ],
        "allow_login" => [
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
    const FIELD_SEARCHABLE = ["role_code", "role_name"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = ["id", "role_code", "role_name", "role_group_id", "description", "allow_login", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_UNIQUE = [["role_code"]];
    const FIELD_UPLOAD = [];
    const FIELD_TYPE = [
        "id" => "bigint",
        "role_code" => "character_varying",
        "role_name" => "character_varying",
        "role_group_id" => "bigint",
        "description" => "text",
        "allow_login" => "boolean",
        "active" => "boolean",
        "created_by" => "bigint",
        "updated_by" => "bigint",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [
        "role_code" => "",
        "role_name" => "",
        "role_group_id" => "",
        "description" => "",
        "allow_login" => "true",
        "active" => "true",
        "created_by" => "",
        "updated_by" => "",
        "created_at" => "",
        "updated_at" => "",
    ];
    const FIELD_RELATION = [
        "role_group_id" => [
            "linkTable" => "role_groups",
            "aliasTable" => "B",
            "linkField" => "id",
            "displayName" => "rel_role_group_id",
            "selectFields" => ["id"],
            "selectValue" => "id AS rel_role_group_id"
        ],
    ];
    const CUSTOM_RELATION = [];
    const CUSTOM_SELECT = "";
    const FIELD_VALIDATION = [
        "role_code" => "required|string|max:255",
        "role_name" => "required|string|max:255",
        "role_group_id" => "nullable|integer",
        "description" => "nullable|string",
        "allow_login" => "nullable",
        "active" => "nullable",
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

    public static function beforeInsert($input)
    {
    return $input;
    }

    public static function afterInsert($object, $input)
    {
    return $input;
    }

    public static function beforeUpdate($input)
    {
    return $input;
    }

    public static function afterUpdate($object, $input)
    {
    return $input;
    }

    public static function beforeDelete($input)
    {
    return $input;
    }

    public static function afterDelete($object, $input)
    {
    return $input;
    }// end custom
}
