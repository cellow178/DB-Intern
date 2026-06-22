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


class Permissions extends Model
{
    protected $table = 'permissions';
    protected $dateFormat = 'c';
    const TABLE = "permissions";
    const FILEROOT = "/permissions";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;
    const FIELD_LIST = ["id", "permission_code", "permission_name", "permission_group", "description", "active", "created_at", "updated_at"];
    const FIELD_ADD = ["permission_code", "permission_name", "permission_group", "description", "active"];
    const FIELD_EDIT = ["permission_code", "permission_name", "permission_group", "description", "active"];
    const FIELD_VIEW = ["id", "permission_code", "permission_name", "permission_group", "description", "active", "created_at", "updated_at"];
    const FIELD_READONLY = [];
    const FIELD_FILTERABLE = [
        "id" => [
            "operator" => "=",
        ],
        "permission_code" => [
            "operator" => "=",
        ],
        "permission_name" => [
            "operator" => "=",
        ],
        "permission_group" => [
            "operator" => "=",
        ],
        "description" => [
            "operator" => "=",
        ],
        "active" => [
            "operator" => "=",
        ],
        "created_at" => [
            "operator" => "=",
        ],
        "updated_at" => [
            "operator" => "=",
        ],
    ];
    const FIELD_SEARCHABLE = ["permission_code", "permission_name", "permission_group"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = ["id", "permission_code", "permission_name", "permission_group", "description", "active", "created_at", "updated_at"];
    const FIELD_UNIQUE = [["permission_code"]];
    const FIELD_UPLOAD = [];
    const FIELD_TYPE = [
        "id" => "bigint",
        "permission_code" => "character_varying",
        "permission_name" => "character_varying",
        "permission_group" => "character_varying",
        "description" => "text",
        "active" => "boolean",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [
        "permission_code" => "",
        "permission_name" => "",
        "permission_group" => "",
        "description" => "",
        "active" => "true",
        "created_at" => "",
        "updated_at" => "",
    ];
    const FIELD_RELATION = [
    ];
    const CUSTOM_RELATION = [];
    const CUSTOM_SELECT = "";
    const FIELD_VALIDATION = [
        "permission_code" => "required|string|max:100",
        "permission_name" => "required|string|max:200",
        "permission_group" => "nullable|string|max:200",
        "description" => "required|string",
        "active" => "nullable",
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
