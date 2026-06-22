<?php

namespace App\Services\Crud;

use App\CoreService\CallService;
use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Add extends CoreService
{

    public $transaction = true;
    public $task = null;

    public function prepare($input)
    {
        $model = str_replace('_', '-', $input["model"]);
        $classModel = "\\App\\Models\\" . Str::ucfirst(Str::camel($model));
        $permission = "create-" . $model;

        // $authRoles = getRoleName(Auth::user()->role_id);
        if (!class_exists($classModel))
            throw new CoreException(__("message.model404", ['model' => $model]), 404);
        if (!$classModel::IS_ADD)
            throw new CoreException("Not found", 404);
        if (!hasPermission($permission))
            throw new CoreException(__("message.forbidden403", ['permission' => $permission]), 403);
        $input["class_model"] = $classModel;
        $input["model"] = $model;

        if ($classModel::FIELD_ARRAY) {
            foreach ($classModel::FIELD_ARRAY as $item) {
                $input[$item] = serialize($input[$item]);
            }
        }

        if ($classModel::FIELD_UPLOAD) {
            foreach ($classModel::FIELD_UPLOAD as $item) {
                if (isset($input[$item])) {
                    if (is_array($input[$item])) {
                        $input[$item] = isset($input[$item]["path"]) ? $input[$item]["path"] : $input[$item]["field_value"];
                    }
                }
            }
        }

        $validator = Validator::make($input, $classModel::FIELD_VALIDATION);

        if ($validator->fails()) {
            throw new CoreException($validator->errors()->first());
        }

        if ($classModel::FIELD_UNIQUE) {
            foreach ($classModel::FIELD_UNIQUE as $search) {
                $query = $classModel::whereRaw("true");
                $fieldTrans = [];
                foreach ($search as $key) {
                    if (isset($input[$key])) {
                        $fieldTrans[] = __("field.$key");
                        $query->where($key, $input[$key]);
                    }
                };
                $isi = $query->first();
                if (!is_null($isi) and !empty($fieldTrans)) {
                    throw new CoreException(__("message.alreadyExist", ['field' => implode(",", $fieldTrans)]));
                }
            }
        }

        // VALIDATION DATA
        if ($classModel::FIELD_VALIDATION_DATA) {
            foreach ($classModel::FIELD_VALIDATION_DATA as $key => $relation) {
                if (isset($input[$key])) {
                    $table = $relation["table"];
                    $validation = $relation["validation"];

                    $filter = array();
                    $filterValue = array();
                    foreach ($validation as $field => $b) {
                        $value = $b['value'];
                        $filter[] = $field . " = ?";
                        if ($value == 'current_value') {
                            $val = $input[$key];
                        } else if ($value == 'parent_value') {
                            $val = $input[$b['parent_id']];
                        }
                        $filterValue[] = $val;
                    }
                    $filter = count($filter) > 0 ? implode(" AND ", $filter) : "";
                    $isExist = DB::selectOne("SELECT * FROM " . $table . " WHERE " . $filter, $filterValue);

                    if (!$isExist) {
                        throw new CoreException(__("message.fieldIDNotFound", ['field' => $key]));
                    }
                }
            }
        }

        return $input;
    }

    public function process($input, $originalInput)
    {
        $response = [];
        $classModel = $input["class_model"];
        $model = $input["model"];

        $input = $classModel::beforeInsert($input);

        $object = new $classModel;
        foreach ($classModel::FIELD_ADD as $item) {
            if ($item == "created_by") {
                $input[$item] = Auth::id();
            }
            if ($item == "updated_by") {
                $input[$item] = Auth::id();
            }
            if (isset($input[$item])) {
                $inputValue = $input[$item] ?? $classModel::FIELD_DEFAULT_VALUE[$item];
                $object->{$item} = ($inputValue !== '') ? $inputValue : null;
            }
        }

        // MOVE FILE
        $tmpPaths = array();
        foreach ($classModel::FIELD_UPLOAD as $item) {
            $tmpPath = $input[$item] ?? null;
            if (!is_null($tmpPath)) {
                if (!Storage::exists($tmpPath)) {
                    throw new CoreException(__("message.tempFileNotFound", ['field' => $item]));
                }
                $tmpPath = $input[$item];
                $originalname = pathinfo(storage_path($tmpPath), PATHINFO_FILENAME);
                $ext = pathinfo(storage_path($tmpPath), PATHINFO_EXTENSION);

                $newPath = "/" . date("Y") . "/" . date("Ym") . $classModel::FILEROOT . "/" . $originalname . "." . $ext;
                //START MOVE FILE
                if (Storage::exists($newPath)) {

                    $id = 1;
                    $filename = pathinfo(storage_path($newPath), PATHINFO_FILENAME);
                    $ext = pathinfo(storage_path($newPath), PATHINFO_EXTENSION);
                    while (true) {
                        $originalname = $filename . "($id)." . $ext;
                        if (!Storage::exists("/" . date("Y") . "/" . date("Ym") . $classModel::FILEROOT . "/" . $originalname))
                            break;
                        $id++;
                    }
                    $newPath = "/" . date("Y") . "/" . date("Ym") . $classModel::FILEROOT . "/" . $originalname;
                }

                $ext = pathinfo(storage_path($newPath), PATHINFO_EXTENSION);
                $object->{$item} = $newPath;
                Storage::copy($tmpPath, $newPath);

                $tmpPaths[] = $tmpPath;
            }
        }
        //END MOVE FILE

        $object->save();
        $displayedDataAfterInsert["id"] = $object->id;
        $displayedDataAfterInsert["model"] = $classModel::TABLE;
        $displayedDataAfterInsert = CallService::run("Find", $displayedDataAfterInsert);
        if (isset($displayedDataAfterInsert->original["data"])) {
            $displayedDataAfterInsert = $displayedDataAfterInsert->original["data"];
        } else {
            $displayedDataAfterInsert = $displayedDataAfterInsert->original;
            throw new CoreException($displayedDataAfterInsert, 500);
        }

        //AFTER INSERT
        $afterInsertedRespnese = $classModel::afterInsert($displayedDataAfterInsert, $input);
        unset($afterInsertedRespnese['model']);
        unset($afterInsertedRespnese['class_model']);

        // START INSERT CHILD DATA
        foreach ($classModel::CHILD_TABLE as $keyItem => $valItem) {
            $childModuleName = "child_data_" . $keyItem;
            $foreignFieldName = $valItem["foreignField"];
            if (isset($input[$childModuleName])) {
                foreach ($input[$childModuleName] as $childItem) {
                    $childItem["model"] = $keyItem;
                    $childItem[$foreignFieldName] = $object->id;
                    $childDataInserted = CallService::run("Add", $childItem);
                    
                    if (!$childDataInserted->original['success'])
                        throw new CoreException($childDataInserted->original['message']);
                }
            }
        }

        /* ADD LOG ACTIVITY */
        if (!isset($input["module_name"])) $input["module_name"] = str_replace('-', '_', $model);
        // logActivity(("create-".$model), $displayedDataAfterInsert->id, ($input["activity_id"] ?? null), ($input["module_name"] ?? null), ($input["report_number"] ?? null), ($input["section_id"] ?? null));

        // END INSERT CHILD DATA
        $response["data"] = $displayedDataAfterInsert;
        $response["after_inserted_response"] = $afterInsertedRespnese;
        Storage::delete($tmpPaths);

        $response["message"] = __("message.successfullyAdd");

        return $response;
    }

    protected function validation()
    {
        return [];
    }
}
