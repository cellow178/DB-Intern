<?php

namespace App\Services\Crud;

use App\CoreService\CallService;
use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class Edit extends CoreService
{

    public $transaction = true;
    public $task = null;

    public function prepare($input)
    {
        $model = str_replace('_', '-', $input["model"]);
        $classModel = "\\App\\Models\\" . Str::ucfirst(Str::camel($model));
        $permission = "update-" . $model;

        if (!class_exists($classModel))
            throw new CoreException(__("message.model404", ['model' => $model]), 404);

        if (!$classModel::IS_EDIT)
            throw new CoreException("Not found", 404);

        if (!hasPermission($permission))
            throw new CoreException("Forbidden", 403);
        $input["class_model"] = $classModel;
        $input["model"] = $model;

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
        return ["a" => $input];
        $classModel = $input["class_model"];
        $model = $input["model"];
        $object = $classModel::find($input["id"]);
        if (!$object) {
            throw new CoreException(__("message.dataNotFound", ['id' => $input["id"]]));
        }
        $rules = $classModel::FIELD_VALIDATION;
        $rules["id"] = "required|integer";

        if ($classModel::FIELD_ARRAY) {
            foreach ($classModel::FIELD_ARRAY as $item) {
                $input[$item] = serialize($input[$item]);
            }
        }
        //SEBELUM DIVALIDASI UBAH DULU DATA OBJECT YANG DIKIRMKAN FRONE END JADI STRING,
        //TERUTAMA KOLOM UPLOAD FILE
        foreach ($classModel::FIELD_UPLOAD as $item) {
            if (array_key_exists($item, $input)) {
                if (is_array($input[$item])) {
                    $input[$item] = isset($input[$item]["path"]) ? $input[$item]["path"] : $input[$item]["field_value"];
                }
            }
        }
        // END

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            throw new CoreException($validator->errors()->first());
        }

        ///

        $validator = Validator::make($input, $classModel::FIELD_VALIDATION);

        if ($validator->fails()) {
            throw new CoreException($validator->errors()->first());
        }


        if ($classModel::FIELD_UNIQUE) {
            foreach ($classModel::FIELD_UNIQUE as $search) {
                $query = $classModel::whereRaw("true");
                $fieldTrans = [];
                $uniqueChange = false;
                foreach ($search as $key) {
                    if ($input[$key] != $object->{$key}) {
                        $uniqueChange = true;
                    }
                    $fieldTrans[] = __("field.$key");
                    $query->where($key, $input[$key]);
                };

                if ($uniqueChange) {
                    $isi = $query->first();
                    if (!is_null($isi)) {
                        throw new CoreException(__("message.alreadyExist", ['field' => implode(",", $fieldTrans)]));
                    }
                }
            }
        }
        $input = $classModel::beforeUpdate($input);

        // START MOVE FILE
        $tmpPaths = [];
        foreach ($classModel::FIELD_UPLOAD as $item) {
            if (isset($input[$item])) {
                if (is_null($input[$item])) {
                    $object->{$item} = null;
                } else if ($object->{$item} !== $input[$item]) {
                    $tmpPath = $input[$item] ?? null;
                    if (!is_null($tmpPath)) {
                        if (!Storage::exists($tmpPath)) {
                            throw new CoreException(__("message.tempFileNotFound", ['field' => $item]));
                        }
                        $tmpPath = $input[$item] ?? null;

                        $originalname = pathinfo(storage_path($tmpPath), PATHINFO_FILENAME);
                        $ext = pathinfo(storage_path($tmpPath), PATHINFO_EXTENSION);

                        $newPath = "/" . date("Y") . "/" . date("Ym") . $classModel::FILEROOT . "/" . $originalname . "." . $ext;

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
                        //OLD FILE DELETE
                        $oldFilePath = $object->{$item};
                        if (isset($oldFilePath)) {
                            $tmpPaths[] = $oldFilePath;
                        }

                        //END MOVE FILE
                        $object->{$item} = $newPath;
                        Storage::copy($tmpPath, $newPath);

                        $tmpPaths[] = $tmpPath;
                        //END MOVE FILE
                    } else {
                        //OLD FILE DELETE
                        $oldFilePath = $object->{$item};
                        $oldFilePath = $object->{$item};
                        if (isset($oldFilePath)) {
                            $tmpPaths[] = $oldFilePath;
                        }
                        //END MOVE FILE
                    }
                }
            }
        }
        // END MOVE FILE
        foreach ($classModel::FIELD_EDIT as $item) {
            if ($item == "updated_by") {
                $input[$item] = Auth::id();
            }
            if (array_key_exists($item, $input)) {
                if (!in_array($item, $classModel::FIELD_UPLOAD)) {
                    $inputValue = $input[$item];
                    $object->{$item} = ($inputValue !== '') ? $inputValue : null;
                }
            }
        }

        $object->save();
        // UNTUK FORMAT DATA IMG
        if (!empty($classModel::FIELD_UPLOAD)) {
            foreach ($classModel::FIELD_UPLOAD as $item) {
                if ((preg_match("/file/i", $item) or preg_match("/img/i", $item)) and !is_null($object->$item)) {
                    $object->$item = columnValueToFileObject($item, $object->$item, $classModel::TABLE, $object->id);
                }
            }
        } 
        $afterUpdatedRespnese = $classModel::afterUpdate($object, $input);

        // START INSERT CHILD DATA
        foreach ($classModel::CHILD_TABLE as $keyItem => $valItem) {
            $childModuleName = "child_data_" . $keyItem;
            $foreignFieldName = $valItem["foreignField"];
            
            # GET CHILD DATA
            $classModelChild = "\\App\\Models\\" . Str::ucfirst(Str::camel($keyItem)); 
            $oldData = $classModelChild::where($foreignFieldName, $object['id'])->get()->toArray();
            $deletedItemId = array_diff(array_column($oldData, 'id'), array_column($input[$childModuleName], 'id'));
            
            # Remove deleted item 
            $deleteItem = $classModelChild::where($foreignFieldName, $object['id'])->whereIn('id', $deletedItemId)->delete();
            if (isset($input[$childModuleName])) {
                foreach ($input[$childModuleName] as $childItem) {
                    $childItem[$foreignFieldName] = $object->id;
                    if (isset($childItem["id"])) {
                        $childItem["model"] = $keyItem;
                        $childDataUpdated = CallService::run("Edit", $childItem);

                        if (!$childDataUpdated->original['success'])
                            throw new CoreException($childDataUpdated->original['message']);
                    } else {
                        $childItem["model"] = $keyItem;
                        $childDataInserted = CallService::run("Add", $childItem);
                        if (!$childDataInserted->original['success'])
                            throw new CoreException($childDataInserted->original['message']);
                    }
                }
            }
        }
        // END INSERT CHILD DATA
        if (count($tmpPaths) > 0) Storage::delete($tmpPaths);

        
        /* ADD LOG ACTIVITY */
        if (!isset($input["module_name"])) $input["module_name"] = str_replace('-', '_', $model);
        // logActivity(("update-".$model), $object->id, ($input["activity_id"] ?? null), ($input["module_name"] ?? null), ($input["report_number"] ?? null), ($input["section_id"] ?? null));

        return [
            "data" => $object,
            "after_updated_response" => $afterUpdatedRespnese,
            "message" => __("message.succesfullyUpdate")
        ];
    }

    protected function validation()
    {
        return [];
    }
}
