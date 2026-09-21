<?php

namespace App\Models;

use App\CoreService\CallService;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class Majors extends Model
{
    protected $table = 'majors';
    protected $dateFormat = 'c';
    protected $fillable = [
        'slug',
        'img_logo',
        'code',
        'major_name',
        'summary',
        'total_classes',
        'major_duration',
        'full_description',
        'active',
        'created_by',
        'updated_by',
    ];

    const TABLE = "majors";
    const FILEROOT = "/majors";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;
    const FIELD_LIST = [
        "id",
        "slug",
        "img_logo",
        "code",
        "major_name",
        "summary",
        "total_classes",
        "major_duration",
        "full_description",
        "active",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at"
    ];
    const FIELD_ADD = [
        "slug",
        "img_logo",
        "code",
        "major_name",
        "summary",
        "total_classes",
        "major_duration",
        "full_description",
        "active",
        "created_by",
        "updated_by"
    ];
    const FIELD_EDIT = [
        "slug",
        "img_logo",
        "code",
        "major_name",
        "summary",
        "total_classes",
        "major_duration",
        "full_description",
        "active",
        "updated_by"
    ];
    const FIELD_VIEW = [
        "id",
        "slug",
        "img_logo",
        "code",
        "major_name",
        "summary",
        "total_classes",
        "major_duration",
        "full_description",
        "active",
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
        "img_logo" => [
            "operator" => "=",
        ],
        "code" => [
            "operator" => "=",
        ],
        "major_name" => [
            "operator" => "=",
        ],
        "summary" => [
            "operator" => "=",
        ],
        "total_classes" => [
            "operator" => "=",
        ],
        "major_duration" => [
            "operator" => "=",
        ],
        "full_description" => [
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
    const FIELD_SEARCHABLE = ["slug", "code", "major_name", "summary"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = [
        "id",
        "slug",
        "img_logo",
        "code",
        "major_name",
        "summary",
        "total_classes",
        "major_duration",
        "full_description",
        "active",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at"
    ];
    const FIELD_UNIQUE = [["slug"]];
    const FIELD_UPLOAD = ["img_logo"];
    const FIELD_TYPE = [
        "id" => "bigint",
        "slug" => "character_varying",
        "img_logo" => "text",
        "code" => "character_varying",
        "major_name" => "character_varying",
        "summary" => "character_varying",
        "total_classes" => "integer",
        "major_duration" => "integer",
        "full_description" => "text",
        "active" => "boolean",
        "created_by" => "bigint",
        "updated_by" => "bigint",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [
        "slug" => "",
        "img_logo" => "",
        "code" => "",
        "major_name" => "",
        "summary" => "",
        "total_classes" => "",
        "major_duration" => "",
        "full_description" => "",
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
        "slug"              => "nullable|string|max:255",
        "img_logo"          => "required|string|exists_file",
        "code"              => "required|string|max:10",
        "major_name"        => "required|string|max:100",
        "summary"           => "required|string|max:255",
        "total_classes"     => "required|integer",
        "major_duration"    => "required|integer",
        "full_description"  => "required|string",
        "active"            => "nullable|boolean",
        "created_by"        => "nullable|integer",
        "updated_by"        => "nullable|integer",
        "created_at"        => "nullable|date",
        "updated_at"        => "nullable|date",
    ];
    const PARENT_CHILD = [];
    // start custom
    const CUSTOM_LIST_FILTER = [];
    const FIELD_CASTING = [];
    const FIELD_VALIDATION_DATA = [];
    const CHILD_TABLE = [];
    const MAPPING_MULTIPLE_ADD = [];

    public function competent(): HasMany
    {
        return $this->hasMany(MajorCompetent::class, 'major_id');
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
        if (empty($input['slug']) && !empty($input['major_name'])) {
            $baseSlug = Str::slug($input['major_name']);
            $slug = $baseSlug;
            $counter = 1;

            while (self::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
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
        if (empty($input['slug']) && !empty($input['major_name'])) {
            $baseSlug = Str::slug($input['major_name']);
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
