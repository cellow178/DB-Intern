<?php

namespace App\Services\Crud;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class Get extends CoreService
{

    public $transaction = false;
    public $task = null;

    public function prepare($input)
    {
        $model = str_replace('_', '-', $input["model"]);
        $classModel = "\\App\\Models\\" . Str::ucfirst(Str::camel($model));
        $permission = "view-" . $model;
        
        if (!class_exists($classModel))
            throw new CoreException(__("message.model404", ['model' => $model]), 404);
        if (!$classModel::IS_LIST)
            throw new CoreException(__("message.404"), 404);
        
        if (!hasPermission($permission))
            throw new CoreException(__("message.forbidden403", ['permission' => $permission]), 403);

        $input["class_model_name"] = $model;
        $input["class_model"] = $classModel;
        return $input;
    }

    public function process($input, $originalInput)
    {
        $classModelName = $input["class_model_name"];
        $classModel = $input["class_model"];

        $selectableList = [];
        $sortBy = defined($classModel . '::DEFAULT_FIELD_SORT_LIST') ? $classModel::DEFAULT_FIELD_SORT_LIST : $classModel::TABLE . ".id";
        $input['sort'] = isset($input["sort"]) ? $input["sort"] : (defined($classModel . '::DEFAULT_FIELD_SORT_LIST') ? $classModel::DEFAULT_SORT_LIST : "DESC");
        $sort = strtoupper($input["sort"] ?? "DESC") == "ASC" ? "ASC" : "DESC";

        $sortableList = $classModel::FIELD_SORTABLE;
        if (in_array($input["sort_by"] ?? "", $sortableList)) {
            $sortBy = $input["sort_by"];
        }

        $searchableList = $classModel::FIELD_SEARCHABLE;

        $tableJoinList = [];
        $filterList = [];
        $params = [];

        foreach ($classModel::FIELD_LIST as $list) {
            $selectableList[] = $classModel::TABLE . "." . $list;
        }

        # FILTER MULTIPLE ~ IF FILTER ARRAY
        foreach ($classModel::FIELD_FILTERABLE as $filter => $operator) {
            if (!is_blank($input, $filter)) {
                if (is_array($input[$filter])) {
                    $input[$filter] = json_decode(json_encode($input[$filter],true));
                    $idsVal = [];
                    foreach ($input[$filter] as $data) {
                        $idsVal[] = json_decode($data)->id;
                    }
                    $input[$filter] = ["operator" => "in", "value" => $idsVal];
                    $input[$filter] = json_encode($input[$filter],true);
                }
            }
        }

        foreach ($classModel::FIELD_FILTERABLE as $filter => $operator) {
            if (!is_blank($input, $filter)) {
                $aliasTable = !isset($operator['aliasTable']) ? $classModel::TABLE : $operator['aliasTable'];
                $cekTypeInput = json_decode($input[$filter], true);
                if (!is_array($cekTypeInput)) {
                    $filterList[] = " AND " . $aliasTable . "." . $filter .  " " . $operator["operator"] . " :$filter";
                    $params[$filter] = $input[$filter];
                } else {
                    $input[$filter] = json_decode($input[$filter], true);
                    if ($input[$filter]["operator"] == 'between') {
                        $filterList[] = " AND " . $aliasTable . "." . $filter .  " " . $input[$filter]["operator"] . " '" . $input[$filter]["value"][0] . "' AND '" . $input[$filter]["value"][1] . "'";
                    } else if ($input[$filter]["operator"] == 'in') {
                        $inValues = "'" . implode("','", $input[$filter]["value"]) . "'";
                        $filterList[] = " AND " . $aliasTable . "." . $filter .  " in (" . $inValues . ")";
                    } else if ($input[$filter]["operator"] == 'ILIKE') {
                        $filterList[] = " AND " . $aliasTable . "." . $filter .  " " . $input[$filter]["operator"] . " '%" . $input[$filter]["value"] . "%'";
                    } else {
                        $filterList[] = " AND " . $aliasTable . "." . $filter .  " " . $input[$filter]["operator"] . " :$filter";
                        $params[$filter] = $input[$filter];
                    }
                }
            }
        }

        ###
        if (method_exists($classModel, 'customFieldFilterable')) {
            $filterList = $classModel::customFieldFilterable($input, $filterList);
        }

        $i = 0;
        foreach ($classModel::FIELD_RELATION as $key => $relation) {
            $alias = $relation["aliasTable"];
            $aliasTableChild = !isset($relation['aliasTableChild']) ? $classModel::TABLE : $relation['aliasTableChild'];

            $fieldDisplayed = "CONCAT_WS (' - ',";
            $searchableRealtionField = "CONCAT_WS (' - ',";
            foreach ($relation["selectFields"] as $keyField) {
                $fieldDisplayed .= $alias . '.' . $keyField . ",";
                $searchableRealtionField .= $alias . '.' . $keyField . ",";
            }
            $fieldDisplayed = substr($fieldDisplayed, 0, strlen($fieldDisplayed) - 1);
            $fieldDisplayed .= ") AS " . $relation["displayName"];
            $selectableList[] = $fieldDisplayed;

            //
            $searchableRealtionField = substr($searchableRealtionField, 0, strlen($searchableRealtionField) - 1);
            $searchableRealtionField .= ")";

            ///
            // $selectableList[] = $alias . "." . $relation["selectValue"];

            $tableJoinList[] = "LEFT JOIN " . $relation["linkTable"] . " " . $alias . " ON " .
                $aliasTableChild . "." . $key . " = " .  $alias . "." . $relation["linkField"];
            $i++;

            //
            if (!in_array($key, ["created_by", "updated_by"]))
                $searchableList[] = $searchableRealtionField;
        }

        if (!empty($classModel::CUSTOM_RELATION)) {
            foreach ($classModel::CUSTOM_RELATION as $customRelation) {
                $tableJoinList[] = $customRelation;
            }
        }

        if (!empty($classModel::CUSTOM_SELECT)) $selectableList[] = $classModel::CUSTOM_SELECT;

        $condition = " WHERE true";

        ###
        if (method_exists($classModel, 'customFilter')) {
            $customFilter = $classModel::customFilter($input);
            $condition .= $customFilter;
        }


        if (!empty($classModel::CUSTOM_LIST_FILTER)) {
            foreach ($classModel::CUSTOM_LIST_FILTER as $customListFilter) {
                $condition .= " AND " . $customListFilter;
            }
        }
        if (!is_blank($input, "search")) {
            $searchableList = array_map(function ($item) use ($classModel) {
                if (in_array($item, $classModel::FIELD_SEARCHABLE)) {
                    return $classModel::TABLE . "." . $item . " ILIKE :search";
                } else {
                    return $item . " ILIKE :search";
                }
            }, $searchableList);
        } else {
            $searchableList = [];
        }

        // return $searchableList;

        if (count($searchableList) > 0 && !is_blank($input, "search"))
            $params["search"] = "%" . strtoupper($input["search"] ?? "") . "%";

        $limit = $input["limit"] ?? 10;
        $offset = $input["offset"] ?? 0;
        if (!is_null($input["page"] ?? null)) {
            $offset = $limit * ($input["page"] - 1);
        }
        if ($limit == "all") {
            $limit = 'null';
            $offset = 'null';
        }

        $sql = "SELECT " . implode(", ", $selectableList) . " FROM " . $classModel::TABLE . " " .
            implode(" ", $tableJoinList) . $condition .
            (count($searchableList) > 0 ? " AND (" . implode(" OR ", $searchableList) . ")" : "") .
            implode("\n", $filterList) . " ORDER BY " . $sortBy . " " . $sort . " LIMIT $limit OFFSET $offset ";

        $sqlForCount = "SELECT COUNT(1) AS total FROM " . $classModel::TABLE . " " .
            implode(" ", $tableJoinList) . $condition .
            (count($searchableList) > 0 ? " AND (" . implode(" OR ", $searchableList) . ")" : "") .
            implode("\n", $filterList);

        $object =  DB::select($sql, $params);

        foreach ($classModel::FIELD_ARRAY as $item) {
        }

        $fieldCasting = $classModel::FIELD_CASTING;
        array_map(function ($key) use ($classModel, $classModelName, $fieldCasting) {
            foreach ($key as $field => $value) {
                if (isset($fieldCasting[$field])) {
                    if (array_key_exists($field, $fieldCasting)) {
                        if ($fieldCasting[$field] == 'float') {
                            $key->$field = (float) $key->$field;
                        } else if ($fieldCasting[$field] == 'array') {
                            $key->$field = json_decode($key->$field);
                        }
                    }
                }
                $key->class_model_name = $classModelName;
                if ((preg_match("/file/i", $field) or preg_match("/img/i", $field) or preg_match("/attachment/i", $field)) and !is_null($key->$field)) {
                    $key->$field = columnValueToFileObject($field, $key->$field, $classModel::TABLE, $key->id);
                }
                if (preg_match("/array_/i", $field)) {
                    $key->$field = unserialize($key->$field);
                    if (!$key->$field) {
                        $key->$field = null;
                    }
                }
                ///
                if (preg_match("/json_/i", $field)) {
                    $key->$field = json_decode($key->$field);
                    if (!$key->$field) {
                        $key->$field = null;
                    }
                }
            }
            return $key;
        }, $object);

        // FOR IMG PHOTO CREATED BY
        array_map(function ($key) use ($classModel, $classModelName) {
            foreach ($key as $field => $value) {
                if (property_exists($key, 'created_by')) {
                    $key->img_photo_created_by = getUserAvatar($key->created_by);
                }
            }
            return $key;
        }, $object);

        /* ======================================
        |              CUSTOMIZE ITEM            |
        ====================================== */
        if (method_exists($classModel, 'customizeList')) {
            array_map(function ($key) use ($classModel, $classModelName) {
                $key = $classModel::customizeList($key);
                return $key;
            }, $object);
        }
        
        $total = DB::selectOne($sqlForCount, $params)->total;
        if ($limit == 'null') {
            $totalPage = 1;
        } else {
            $totalPage = ceil($total / $limit);
        }
        return [
            "data" => $object,
            "total" => $total,
            "totalPage" => $totalPage,
        ];
    }

    protected function validation()
    {
        return [];
    }
}
