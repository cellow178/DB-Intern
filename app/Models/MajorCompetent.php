<?php

namespace App\Models;

use App\CoreService\CallService;
use App\CoreService\CoreException;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class MajorCompetent extends Model
{
    protected $table = 'major_competent';
    protected $dateFormat = 'c';
    protected $fillable = [
        'major_id',
        'competent_name',
        'description',
        'active',
        'created_by',
        'updated_by',
    ];

    const TABLE = "major_competent";
    const FILEROOT = "/major_competent";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;
    const FIELD_LIST = ["id", "major_id", "competent_name", "description", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_ADD = ["major_id", "competent_name", "description", "active", "created_by", "updated_by"];
    const FIELD_EDIT = ["major_id", "competent_name", "description", "active", "updated_by"];
    const FIELD_VIEW = ["id", "major_id", "competent_name", "description", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_READONLY = [];
    const FIELD_FILTERABLE = [
        "id" => [
            "operator" => "=",
        ],
        "major_id" => [
            "operator" => "=",
        ],
        "competent_name" => [
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
    const FIELD_SEARCHABLE = ["competent_name", "description"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = ["id", "major_id", "competent_name", "description", "active", "created_by", "updated_by", "created_at", "updated_at"];
    const FIELD_UNIQUE = [];
    const FIELD_UPLOAD = [];
    const FIELD_TYPE = [
        "id" => "bigint",
        "major_id" => "bigint",
        "competent_name" => "character_varying",
        "description" => "character_varying",
        "active" => "boolean",
        "created_by" => "bigint",
        "updated_by" => "bigint",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [
        "major_id" => "",
        "competent_name" => "",
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
            "selectFields" => ["id"],
            "selectValue" => "id AS rel_major_id"
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
        "major_id"        => "required|integer",
        "competent_name"  => "required|string|max:50",
        "description"     => "required|string|max:255",
        "active"          => "nullable|boolean",
        "created_by"      => "nullable|integer",
        "updated_by"      => "nullable|integer",
        "created_at"      => "nullable|date",
        "updated_at"      => "nullable|date",
    ];
    const PARENT_CHILD = [];
    // start custom
    const CUSTOM_LIST_FILTER = [];
    const FIELD_CASTING = [];
    const FIELD_VALIDATION_DATA = [];
    const CHILD_TABLE = [];
    const MAPPING_MULTIPLE_ADD = [];

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
        if (!empty($input['major_id'])) {
            $major = Majors::find($input['major_id']);
            if (!$major || !$major->active) {
                throw new CoreException('ID jurusan tidak ditemukan atau tidak aktif.');
            }
        }

        return $input;
    }

    public static function afterInsert(mixed $object, array $input): array
    {
        return $input;
    }

    public static function beforeUpdate(array $input): array
    {
        if (!empty($input['major_id'])) {
            $major = Majors::find($input['major_id']);
            if (!$major || !$major->active) {
                throw new CoreException('ID jurusan tidak ditemukan atau tidak aktif.');
            }
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
