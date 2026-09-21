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


class Feedbacks extends Model
{
    protected $table = 'feedbacks';
    protected $dateFormat = 'c';
    protected $fillable = [
        'sender_name',
        'type',
        'category_id',
        'message',
        'created_by',
    ];

    const TABLE = "feedbacks";
    const FILEROOT = "/feedbacks";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;
    const FIELD_LIST = ["id", "sender_name", "type", "category_id", "message", "created_by", "created_at", "updated_at"];
    const FIELD_ADD = ["sender_name", "type", "category_id", "message", "created_by"];
    const FIELD_EDIT = ["sender_name", "type", "category_id", "message"];
    const FIELD_VIEW = ["id", "sender_name", "type", "category_id", "message", "created_by", "created_at", "updated_at"];
    const FIELD_READONLY = [];
    const FIELD_FILTERABLE = [
        "id" => [
            "operator" => "=",
        ],
        "sender_name" => [
            "operator" => "=",
        ],
        "type" => [
            "operator" => "=",
        ],
        "category_id" => [
            "operator" => "=",
        ],
        "message" => [
            "operator" => "=",
        ],
        "created_by" => [
            "operator" => "=",
        ],
        "created_at" => [
            "operator" => "=",
        ],
        "updated_at" => [
            "operator" => "=",
        ],
    ];
    const FIELD_SEARCHABLE = ["sender_name", "message"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = ["id", "sender_name", "type", "category_id", "message", "created_by", "created_at", "updated_at"];
    const FIELD_UNIQUE = [];
    const FIELD_UPLOAD = [];
    const FIELD_TYPE = [
        "id" => "bigint",
        "sender_name" => "character_varying",
        "type" => "boolean",
        "category_id" => "bigint",
        "message" => "text",
        "created_by" => "bigint",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [
        "sender_name" => "",
        "type" => "",
        "category_id" => "",
        "message" => "",
        "created_by" => "",
        "created_at" => "",
        "updated_at" => "",
    ];
    const FIELD_RELATION = [
        "category_id" => [
            "linkTable" => "feedbacks_categories",
            "aliasTable" => "B",
            "linkField" => "id",
            "displayName" => "rel_category_id",
            "selectFields" => ["category_name"],
            "selectValue" => "id AS rel_category_id"
        ],
        "created_by" => [
            "linkTable" => "users",
            "aliasTable" => "C",
            "linkField" => "id",
            "displayName" => "rel_created_by",
            "selectFields" => ["username"],
            "selectValue" => "id AS rel_created_by"
        ],
    ];
    const CUSTOM_RELATION = [];
    const CUSTOM_SELECT = "";
    const FIELD_VALIDATION = [
        "sender_name" => "nullable|string|max:255",
        "type" => "required",
        "category_id" => "required|integer",
        "message" => "required|string",
        "created_by" => "nullable|integer",
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

    public function category()
    {
        return $this->belongsTo(FeedbacksCategories::class, 'category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
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
