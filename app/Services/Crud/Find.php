<?php

namespace App\Services\Crud;

use App\CoreService\CallService;
use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Find extends CoreService
{

    public $transaction = false;
    public $task = null;

    public function prepare($input)
    {
        $model = str_replace('_', '-', $input["model"]);
        $classModel = "\\App\\Models\\" . Str::ucfirst(Str::camel($model));
        $permission = "show-" . $model;

        if (!class_exists($classModel))
            throw new CoreException(__("message.model404", ['model' => $model]), 404);

        if (!$classModel::IS_VIEW)
            throw new CoreException("Not found", 404);

        if (!hasPermission($permission))
            throw new CoreException(__("message.403"), 403);

        $input["class_model"] = $classModel;
        return $input;
    }

    public function process($input, $originalInput)
    {
        $classModel = $input["class_model"];
        $selectableList = [];
        $tableJoinList = [];
        $params = ["id" => $input["id"]];

        foreach ($classModel::FIELD_VIEW as $list) {
            $selectableList[] = $classModel::TABLE . "." . $list;
        }

        $i = 0;
        foreach ($classModel::FIELD_RELATION as $key => $relation) {
            // $alias = toAlpha($i + 1);
            $alias = $relation["aliasTable"];
            $aliasTableChild = !isset($relation['aliasTableChild']) ? $classModel::TABLE : $relation['aliasTableChild'];
            ///
            $fieldDisplayed = "CONCAT_WS (' - ',";
            foreach ($relation["selectFields"] as $keyField) {
                $fieldDisplayed .= $alias . '.' . $keyField . ",";
            }
            $fieldDisplayed = substr($fieldDisplayed, 0, strlen($fieldDisplayed) - 1);
            $fieldDisplayed .= ") AS " . $relation["displayName"];
            $selectableList[] = $fieldDisplayed;
            ///
            // $selectableList[] = $alias . "." . $relation["selectValue"];

            $tableJoinList[] = "LEFT JOIN " . $relation["linkTable"] . " " . $alias . " ON " .
                $aliasTableChild . "." . $key . " = " .  $alias . "." . $relation["linkField"];
            $i++;
        }

        if (!empty($classModel::CUSTOM_RELATION)) {
            foreach ($classModel::CUSTOM_RELATION as $customRelation) {
                $tableJoinList[] = $customRelation;
            }
        }

        if (!empty($classModel::CUSTOM_SELECT)) $selectableList[] = $classModel::CUSTOM_SELECT;

        $condition = " WHERE " . $classModel::TABLE . ".id = :id";

        $sql = "SELECT " . implode(", ", $selectableList) . " FROM " . $classModel::TABLE . " " .
            implode(" ", $tableJoinList) . $condition;



        $object =  DB::selectOne($sql, $params);
        if (is_null($object)) {
            throw new CoreException(__("message.dataNotFound", ['id' => $input["id"]]));
        }

        $fieldCasting = $classModel::FIELD_CASTING;
        if (!empty($fieldCasting)) {
            foreach ($fieldCasting as $item => $k) {
                if (isset($fieldCasting[$item])) {
                    if (array_key_exists($item, $fieldCasting)) {
                        if ($fieldCasting[$item] == 'float') {
                            $object->$item = (float) $object->$item;
                        } else if ($fieldCasting[$item] == 'array') {
                            $object->$item = json_decode($object->$item);
                        }
                    }
                }
            }
        }


        // FORMAT IMAGE
        if (!empty($classModel::FIELD_UPLOAD)) {
            foreach ($classModel::FIELD_UPLOAD as $item) {
                if ((preg_match("/file/i", $item) or preg_match("/img/i", $item) or preg_match("/attachment/i", $item)) and !is_null($object->$item)) {
                    $object->$item = columnValueToFileObject($item, $object->$item, $classModel::TABLE, $object->id); 
                }
                if (preg_match("/array_/i", $item)) {
                    $key->$item = unserialize($key->$item);
                    if (!$key->$item) {
                        $key->$item = null;
                    }
                }
            }
        }

        if (property_exists($object, 'json_data')) {
            $object->json_data =  json_decode($object->json_data);
        }

        // END FOR IMG PHOTO CREATED BY

        // CHILD DATA SHOW
        foreach ($classModel::CHILD_TABLE as $keyItem => $valItem) {
            $childModuleName = "child_data_" . $keyItem;
            $foreignFieldName = $valItem["foreignField"];
            $childItem["model"] = $keyItem;
            $childItem[$foreignFieldName] = $object->id;
            $object->$childModuleName = CallService::run("Dataset", $childItem)->original["data"];
        }

        ###
        if (method_exists($classModel, 'customizeDetail')) {
            $object = $classModel::customizeDetail($object);
        }

        return [
            "data" => $object
        ];
    }

    protected function validation()
    {
        return [];
    }
}
