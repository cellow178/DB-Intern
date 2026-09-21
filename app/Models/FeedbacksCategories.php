<?php

namespace App\Models;

use App\CoreService\CallService;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\CoreService\CoreException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class FeedbacksCategories extends Model
{
    protected $table = 'feedbacks_categories';
    protected $dateFormat = 'c';
    const TABLE = "feedbacks_categories";
    const FILEROOT = "/feedbacks_categories";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;
    const FIELD_LIST = ["id", "category_name", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_ADD = ["category_name", "active", "created_by", "updated_by"];
    const FIELD_EDIT = ["category_name", "active", "updated_by"];
    const FIELD_VIEW = ["id", "category_name", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_READONLY = [];
    const FIELD_FILTERABLE = [
        "id" => [
            "operator" => "=",
        ],
        "category_name" => [
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
    const FIELD_SEARCHABLE = ["category_name"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = ["id", "category_name", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_UNIQUE = [["category_name"]];
    const FIELD_UPLOAD = [];
    const FIELD_TYPE = [
        "id" => "bigint",
        "category_name" => "character_varying",
        "active" => "boolean",
        "created_by" => "bigint",
        "updated_by" => "bigint",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [
        "category_name" => "",
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
    const CUSTOM_SELECT = "(SELECT COUNT(*) FROM feedbacks WHERE feedbacks.category_id = feedbacks_categories.id) AS feedbacks_count";
    const FIELD_VALIDATION = [
        "category_name" => "required|string|max:100",
        "active"        => "nullable|boolean",
        "created_by"    => "nullable|integer",
        "updated_by"    => "nullable|integer",
        "created_at"    => "nullable|date",
        "updated_at"    => "nullable|date",
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

    public function feedbacks()
    {
        return $this->hasMany(Feedbacks::class, 'category_id');
    }

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

    public static function afterUpdate($object, array $input)
    {
        return $input;
    }

    public static function beforeDelete(array $input): array
    {
        $category = self::find($input['id']);

        if ($category && $category->feedbacks()->exists()) {
            throw new CoreException(
                "Kategori feedback '{$category->category_name}' tidak bisa dihapus karena masih digunakan oleh feedback lain.",
                409
            );
        }

        return $input;
    }

    public static function afterDelete($object, array $input)
    {
        return $input;
    } // end custom
}
