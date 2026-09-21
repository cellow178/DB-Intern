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


class Events extends Model
{
    protected $table = 'events';
    protected $dateFormat = 'c';
    const TABLE = "events";
    const FILEROOT = "/events";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;
    const FIELD_LIST = [
        "id",
        "slug",
        "title",
        "content",
        "location",
        "start_date",
        "end_date",
        "img_cover",
        "status",
        "is_highlight",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at"
    ];
    const FIELD_ADD = [
        "slug",
        "title",
        "content",
        "location",
        "start_date",
        "end_date",
        "img_cover",
        "status",
        "is_highlight",
        "created_by",
        "updated_by"
    ];
    const FIELD_EDIT = [
        "slug",
        "title",
        "content",
        "location",
        "start_date",
        "end_date",
        "img_cover",
        "status",
        "is_highlight",
        "updated_by"
    ];
    const FIELD_VIEW = [
        "id",
        "slug",
        "title",
        "content",
        "location",
        "start_date",
        "end_date",
        "img_cover",
        "status",
        "is_highlight",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at"
    ];
    const FIELD_READONLY = [];
    const FIELD_FILTERABLE = [
        "id" => [
            "operator" => "=",
        ],
        "slug" => [
            "operator" => "=",
        ],
        "title" => [
            "operator" => "=",
        ],
        "content" => [
            "operator" => "=",
        ],
        "location" => [
            "operator" => "=",
        ],
        "start_date" => [
            "operator" => "=",
        ],
        "end_date" => [
            "operator" => "=",
        ],
        "img_cover" => [
            "operator" => "=",
        ],
        "status" => [
            "operator" => "=",
        ],
        "is_highlight" => [
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
    const FIELD_SEARCHABLE = ["slug", "title", "location", "status"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = [
        "id",
        "slug",
        "title",
        "content",
        "location",
        "start_date",
        "end_date",
        "img_cover",
        "status",
        "is_highlight",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at"
    ];
    const FIELD_UNIQUE = [["slug"]];
    const FIELD_UPLOAD = ["img_cover"];
    const FIELD_TYPE = [
        "id" => "bigint",
        "slug" => "character_varying",
        "title" => "character_varying",
        "content" => "text",
        "location" => "character_varying",
        "start_date" => "date",
        "end_date" => "date",
        "img_cover" => "text",
        "status" => "character_varying",
        "is_highlight" => "boolean",
        "created_by" => "bigint",
        "updated_by" => "bigint",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [
        "slug" => "",
        "title" => "",
        "content" => "",
        "location" => "",
        "start_date" => "",
        "end_date" => "",
        "img_cover" => "",
        "status" => "",
        "is_highlight" => "false",
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
        "slug" => "required|string|max:255",
        "title" => "required|string|max:255",
        "content" => "required|string",
        "location" => "required|string|max:255",
        "start_date" => "required",
        "end_date" => "required",
        "img_cover" => "nullable|string|exists_file",
        "status" => "required|string|max:255",
        "is_highlight" => "required",
        "created_by" => "nullable|integer",
        "updated_by" => "nullable|integer",
        "created_at" => "nullable|date",
        "updated_at" => "nullable|date",
    ];
    const PARENT_CHILD = [];
    // start custom
    const CUSTOM_LIST_FILTER = [];
    const FIELD_CASTING = [
        "start_date" => "date",
        "end_date"   => "date",
        "is_highlight" => "boolean",
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

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public static function beforeInsert(array $input): array
    {
        if (!empty($input['title'])) {
            $slug = \Illuminate\Support\Str::slug($input['title']);
            $originalSlug = $slug;
            $count = 1;

            while (self::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $input['slug'] = $slug;
        }

        return $input;
    }

    public static function afterInsert(mixed $object, array $input): array
    {
        return $input;
    }

    public static function beforeUpdate(array $input): array
    {
        if (!empty($input['title'])) {
            $baseSlug = \Illuminate\Support\Str::slug($input['title']);
            $slug = $baseSlug;
            $counter = 1;
            $id = $input['id'] ?? null;

            while (self::where('slug', $slug)->when($id, fn($q) => $q->where('id', '!=', $id))->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $input['slug'] = $slug;
        }

        return $input;
    }

    public static function afterUpdate(mixed $object, array $input): array
    {
        if (isset($input['is_highlight']) && filter_var($input['is_highlight'], FILTER_VALIDATE_BOOLEAN)) {
            self::where('id', '!=', $object->id)
                ->where('is_highlight', true)
                ->update(['is_highlight' => false]);
        }

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
