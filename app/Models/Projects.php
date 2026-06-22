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


class Projects extends Model
{
    protected $table = 'projects';
    protected $dateFormat = 'c';
    const TABLE = "projects";
    const FILEROOT = "/projects";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;
    const FIELD_LIST = ["id", "project_number", "project_name", "year", "img_logo", "description", "active", "created_by", "updated_by", "created_at", "updated_at", "department_id", "address", "start_date", "end_date", "project_sync_id", "project_manager"];
    const FIELD_ADD = ["project_number", "project_name", "year", "img_logo", "description", "active", "created_by", "updated_by", "department_id", "address", "start_date", "end_date", "project_sync_id", "project_manager"];
    const FIELD_EDIT = ["project_number", "project_name", "year", "img_logo", "description", "active", "updated_by", "department_id", "address", "start_date", "end_date", "project_sync_id", "project_manager"];
    const FIELD_VIEW = ["id", "project_number", "project_name", "year", "img_logo", "description", "active", "created_by", "updated_by", "created_at", "updated_at", "department_id", "address", "start_date", "end_date", "project_sync_id", "project_manager"];
    const FIELD_READONLY = [];
    const FIELD_FILTERABLE = [
        "id" => [
            "operator" => "=",
        ],
        "project_number" => [
            "operator" => "=",
        ],
        "project_name" => [
            "operator" => "=",
        ],
        "year" => [
            "operator" => "=",
        ],
        "img_logo" => [
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
        "department_id" => [
            "operator" => "=",
        ],
        "address" => [
            "operator" => "=",
        ],
        "start_date" => [
            "operator" => "=",
        ],
        "end_date" => [
            "operator" => "=",
        ],
        "project_sync_id" => [
            "operator" => "=",
        ],
        "project_manager" => [
            "operator" => "=",
        ],
    ];
    const FIELD_SEARCHABLE = ["project_number", "project_name", "img_logo", "project_manager"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = ["id", "project_number", "project_name", "year", "img_logo", "description", "active", "created_by", "updated_by", "created_at", "updated_at", "department_id", "address", "start_date", "end_date", "project_sync_id", "project_manager"];
    const FIELD_UNIQUE = [["project_name"]];
    const FIELD_UPLOAD = ["img_logo"];
    const FIELD_TYPE = [
        "id" => "integer",
        "project_number" => "character_varying",
        "project_name" => "character_varying",
        "year" => "integer",
        "img_logo" => "character_varying",
        "description" => "text",
        "active" => "integer",
        "created_by" => "bigint",
        "updated_by" => "bigint",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
        "department_id" => "integer",
        "address" => "text",
        "start_date" => "date",
        "end_date" => "date",
        "project_sync_id" => "integer",
        "project_manager" => "character_varying",
    ];

    const FIELD_DEFAULT_VALUE = [
        "project_number" => "",
        "project_name" => "",
        "year" => "",
        "img_logo" => "",
        "description" => "",
        "active" => "1",
        "created_by" => "",
        "updated_by" => "",
        "created_at" => "",
        "updated_at" => "",
        "department_id" => "1",
        "address" => "",
        "start_date" => "",
        "end_date" => "",
        "project_sync_id" => "",
        "project_manager" => "",
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
        "project_number" => "nullable|string|max:255",
        "project_name" => "required|string|max:255",
        "year" => "nullable|integer",
        "img_logo" => "nullable|string|max:255|exists_file",
        "description" => "nullable|string",
        "active" => "nullable|integer",
        "created_by" => "nullable|integer",
        "updated_by" => "nullable|integer",
        "created_at" => "nullable|date",
        "updated_at" => "nullable|date",
        "department_id" => "nullable|integer",
        "address" => "nullable|string",
        "start_date" => "nullable",
        "end_date" => "nullable",
        "project_sync_id" => "nullable|integer",
        "project_manager" => "nullable|string|max:255",
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
