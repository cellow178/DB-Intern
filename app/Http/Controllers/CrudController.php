<?php

namespace App\Http\Controllers;

use App\CoreService\CallService;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class CrudController extends Controller
{
    public function test(){
        return "test";
    }
    public function index($model)
    {
        $input = request()->all();
        $input["model"] = $model;
        return CallService::run("Get", $input);
    }

    public function dataset($model)
    {
        $input = request()->all();
        $input["model"] = $model;
        return CallService::run("Dataset", $input);
    }

    public function datasetUnselected($model, $mappingModel) {
        $input = request()->all();
        $input["model"] = $model;
        $input["mapping_model"] = $mappingModel;
        return CallService::run("DatasetUnselected", $input);
    }

    public function show($model, $id)
    {
        $input = request()->all();
        $input["id"] = $id;
        $input["model"] = $model;
        return CallService::run("Find", $input);
    }

    public function create($model)
    {
        $input = request()->all();
        $input["model"] = $model;
        return CallService::run("Add", $input);
    }

    public function update($model, $id)
    {
        $input = request()->all();
        $input["id"] = $id;
        $input["model"] = $model;
        return CallService::run("Edit", $input);
    }

    public function delete($model, $id)
    {
        $input = request()->all();
        $input["model"] = $model;
        $input["id"] = $id;
        return CallService::run("Delete", $input);
    }

    private function forbidden()
    {
        return response()->json([
            "message" => __("message.403")
        ], 403);
    }

    private function notFound()
    {
        return response()->json([
            "message" => __("message.404")
        ], 404);
    }

    public function generate($model)
    {
        $classModel = "\\App\\Models\\" . Str::ucfirst(Str::camel($model));
        return [
            "table" => $classModel::TABLE,
            "primaryKey" => "id",
            "isList" => hasPermission("view-" . $model),
            "isView" => hasPermission("view-" . $model),
            "isEdit" => hasPermission("edit-" . $model),
            "isAdd" => hasPermission("add-" . $model),
            "isDelete" => hasPermission("delete-" . $model),
            "fieldList" => $classModel::FIELD_LIST,
            "fieldView" => $classModel::FIELD_VIEW,
            "fieldEdit" => $classModel::FIELD_EDIT,
            "fieldAdd" => $classModel::FIELD_ADD,
            "fieldReadonly" => $classModel::FIELD_READONLY,
            "fieldFilterable" => $classModel::FIELD_FILTERABLE,
            "fieldSearchable" => $classModel::FIELD_SEARCHABLE,
            "fieldType" => $classModel::FIELD_TYPE,
            "fieldRelation" => $classModel::FIELD_RELATION,
            "fieldValidation" => $classModel::FIELD_VALIDATION,
            "parentChild" => $classModel::PARENT_CHILD
        ];
    }

    public function listModule()
    {
        return
            __("modul");
    }

    public function lang()
    {
        return [
            "id" => [
                "modul" => __("modul", [], "id"),
                "field" => __("field", [], "id")
            ],
            "en" => [
                "modul" => __("modul", [], "en"),
                "field" => __("field", [], "en")
            ]
        ];
    }
}
