<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\CoreService\CoreException;

class News extends Model
{
    protected $table = 'news';
    protected $dateFormat = 'c';

    protected $fillable = [
        'category_id',
        'slug',
        'title',
        'content',
        'img_cover',
        'status',
        'is_highlight',
        'created_by',
        'updated_by',
    ];

    const TABLE = "news";
    const FILEROOT = "/news";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;

    const FIELD_LIST = [
        "id",
        "slug",
        "title",
        "img_cover",
        "status",
        "is_highlight",
        "created_by",
        "updated_by",
        "created_at"
    ];
    const FIELD_ADD = [
        "category_id",
        "slug",
        "title",
        "content",
        "img_cover",
        "status",
        "is_highlight",
        "created_by",
        "updated_by"
    ];
    const FIELD_EDIT = [
        "category_id",
        "slug",
        "title",
        "content",
        "img_cover",
        "status",
        "is_highlight",
        "updated_by"
    ];
    const FIELD_VIEW = [
        "id",
        "category_id",
        "slug",
        "title",
        "content",
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
        "category_id" => [
            "operator" => "=",
        ],
        "status" => [
            "operator" => "=",
        ],
        "is_highlight" => [
            "operator" => "=",
        ],
        "created_at" => [
            "operator" => "between",
        ],
    ];

    const FIELD_SEARCHABLE = [
        "slug",
        "title",
        "status",
        "content"
    ];

    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = ["id", "category_id", "slug", "title", "status", "created_at", "updated_at"];
    const FIELD_UNIQUE = [["slug"]];
    const FIELD_UPLOAD = ["img_cover"];

    const FIELD_TYPE = [
        "id" => "bigint",
        "category_id" => "bigint",
        "slug" => "character_varying",
        "title" => "character_varying",
        "content" => "text",
        "img_cover" => "text",
        "status" => "character_varying",
        "is_highlight" => "boolean",
        "created_by" => "bigint",
        "updated_by" => "bigint",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [
        "category_id"  => "",
        "slug"         => "",
        "title"        => "",
        "content"      => "",
        "img_cover"    => "",
        "status"       => "draft",
        "is_highlight" => false,
        "created_by"   => "",
        "updated_by"   => "",
        "created_at"   => "",
        "updated_at"   => "",
    ];

    const FIELD_RELATION = [
        "category_id" => [
            "linkTable" => "news_categories",
            "aliasTable" => "B",
            "linkField" => "id",
            "displayName" => "rel_category_id",
            "selectFields" => ["name"],
            "selectValue" => "id AS rel_category_id"
        ],
        "created_by" => [
            "linkTable" => "users",
            "aliasTable" => "C",
            "linkField" => "id",
            "displayName" => "rel_created_by",
            "selectFields" => ["fullname"],
            "selectValue" => "id AS rel_created_by"
        ],
        "updated_by" => [
            "linkTable" => "users",
            "aliasTable" => "D",
            "linkField" => "id",
            "displayName" => "rel_updated_by",
            "selectFields" => ["fullname"],
            "selectValue" => "id AS rel_updated_by"
        ],
    ];

    const CUSTOM_RELATION = [];
    const CUSTOM_SELECT = "";
    const FIELD_VALIDATION = [
        "category_id"  => "nullable|integer",
        "slug"         => "nullable|string|max:255",
        "title"        => "required|string|min:10|max:255",
        "content"      => "required|string",
        "img_cover"    => "nullable|string",
        "status"       => "nullable|in:draft,publish,archive",
        "is_highlight" => "nullable|boolean",
        "created_by"   => "nullable|integer",
        "updated_by"   => "nullable|integer",
        "created_at"   => "nullable|date",
        "updated_at"   => "nullable|date",
    ];

    const PARENT_CHILD = [];
    const CUSTOM_LIST_FILTER = [];
    const FIELD_CASTING = [];
    const FIELD_VALIDATION_DATA = [];
    const CHILD_TABLE = [];
    const MAPPING_MULTIPLE_ADD = [];

    public function category()
    {
        return $this->belongsTo(NewsCategories::class, 'category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // start custom

    public static function beforeInsert(array $input)
    {
        if (!empty($input['category_id'])) {
            $category = NewsCategories::find($input['category_id']);
            if (!$category || !$category->active) {
                throw new CoreException('Kategori yang dipilih tidak ditemukan atau tidak aktif.');
            }
        }

        if (empty($input['slug']) && !empty($input['title'])) {
            $baseSlug = Str::slug($input['title']);
            $slug = $baseSlug;
            $counter = 1;

            while (self::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $input['slug'] = $slug;
        }

        return $input;
    }

    public static function afterInsert(mixed $object, array $input)
    {
        return $input;
    }

    public static function beforeUpdate(array $input)
    {
        if (!empty($input['category_id'])) {
            $category = NewsCategories::find($input['category_id']);
            if (!$category || !$category->active) {
                throw new CoreException('Kategori yang dipilih tidak ditemukan atau tidak aktif.');
            }
        }

        if (empty($input['slug']) && !empty($input['title'])) {
            $baseSlug = Str::slug($input['title']);
            $slug = $baseSlug;
            $counter = 1;

            $id = $input['id'] ?? null;

            while (self::where('slug', $slug)->when($id, fn($q) => $q->where('id', '!=', $id))->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $input['slug'] = $slug;
        }

        return $input;
    }

    public static function afterUpdate(mixed $object, array $input)
    {
        if (!empty($input['is_highlight']) && $input['is_highlight'] == true) {
            self::where('id', '!=', $object->id)
                ->where('is_highlight', true)
                ->update(['is_highlight' => false]);
        }

        return $input;
    }

    public static function beforeDelete(array $input)
    {
        return $input;
    }

    public static function afterDelete(mixed $object, array $input)
    {
        return $input;
    }

    public static function customizeList(mixed $key)
    {
        return $key;
    }

    public static function customizeDetail(mixed $object)
    {
        return $object;
    }
    // end custom
}
