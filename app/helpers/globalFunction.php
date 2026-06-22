<?php

use App\CoreService\CoreException;
use App\Models\User;
use App\Services\Auth\FcmService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tymon\JWTAuth\Facades\JWTAuth;

if (!function_exists('selectableListBuilder')) {

    function selectableListBuilder($fieldList, $tableName) 
    {
        $selectableList = [];
        foreach ($fieldList as $list) {
            $selectableList[] = $tableName . "." . $list;
        }

        return $selectableList;
    }
}

if (!function_exists('toAlpha')) {

    function toAlpha($data)
    {
        $alphabet =   array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',
            'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
        );
        return $alphabet[$data];
    }
}

if (!function_exists('get_string_between')) {

    function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
}

if (!function_exists('arrayToString')) {

    function arrayToString($array)
    {
        $list = [];
        foreach ($array as $value) {
            if (is_array($value))
                $list[] = arrayToString($value);
            else
                $list[] = '"' . $value . '"';
        }
        return "[" . implode(", ", $list) . "]";
    }
}

if (!function_exists('filterListBuilder')) {
    
    function filterListBuilder($fieldFilterable, $tableName, $input)
    {
        $params = [];
        $filterList = [];
        # FILTER MULTIPLE ~ IF FILTER ARRAY
        foreach ($fieldFilterable as $filter => $operator) {
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

        foreach ($fieldFilterable as $filter => $operator) {
            if (!is_blank($input, $filter)) {
                $aliasTable = !isset($operator['aliasTable']) ? $tableName : $operator['aliasTable'];
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

        return [
            "filter_list" => $filterList,
            "params" => $params,
        ];
    }
}

if (!function_exists('tableJoinListBuilder')) {

    function tableJoinListBuilder($fieldRelation, $tableName)
    {
        $tableJoinList = [];
        $selectableList = [];
        $searchableList = [];

        $i = 0;
        foreach ($fieldRelation as $key => $relation) {
            $alias = $relation["aliasTable"];
            $aliasTableChild = !isset($relation['aliasTableChild']) ? $tableName : $relation['aliasTableChild'];

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

            // $selectableList[] = $alias . "." . $relation["selectValue"];/

            $tableJoinList[] = "LEFT JOIN " . $relation["linkTable"] . " " . $alias . " ON " .
                $aliasTableChild . "." . $key . " = " .  $alias . "." . $relation["linkField"];
            $i++;

            //
            if (!in_array($key, ["created_by", "updated_by"]))
                $searchableList[] = $searchableRealtionField;
        }

        return [
            "table_join" => $tableJoinList,
            "selectable_list" => $selectableList,
            "searchable_list" => $searchableList
        ];
    }
}

if (!function_exists('columnValueToFileObject')) {

    function columnValueToFileObject($fieldName, $fieldValue, $classModelTable, $id)
    {
        $url = 'api/file/' . $classModelTable . '/' . $fieldName . '/' . $id . '/' . time();
        $tumbnailUrl = 'api/tumb-file/' . $classModelTable . '/' . $fieldName . '/' . $id . '/' . time();
        $ext = pathinfo($fieldValue, PATHINFO_EXTENSION);
        $filename = pathinfo(storage_path($fieldValue), PATHINFO_BASENAME);
        
        $objectFile = (object) [
            "ext" => (is_null($fieldValue)) ? null : $ext,
            "url" => $url,
            "tumbnail_url" => $tumbnailUrl,
            "filename" => (is_null($fieldValue)) ? null : $filename,
            "field_value" => $fieldValue
        ];
        return $objectFile;
    }
}

if (!function_exists('fileObjectToColumnValue')) {

    function fileObjectToColumnValue($fileObject)
    {
        if (is_array($fileObject)) {
            $columnValue = isset($fileObject["path"]) ? $fileObject["path"] : $fileObject["field_value"];
        } else {
            $columnValue = $fileObject;
        }
        return $columnValue;
    }
}

if (!function_exists('copyOrMoveFilePath')) {

    function copyOrMoveFilePath($fieldName, $fieldValue, $classModelTable, $type = "move")
    {
        $model = str_replace('_', '-', $classModelTable);
        $classModel = "\\App\\Models\\" . Str::ucfirst(Str::camel($model));

        $tmpPath = $fieldValue ?? null;
        
        if (!Storage::exists($tmpPath)) {
            throw new CoreException(__("message.tempFileNotFound", ['field' => $fieldName]));
        }
        $tmpPath = $fieldValue;
        $originalname = $fieldName . "_" . pathinfo(storage_path($tmpPath), PATHINFO_FILENAME);
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
        Storage::$type($tmpPath, $newPath);
        //END MOVE FILE

        return $newPath ?? null;
    }
}

if (!function_exists('kolomkeN')) {
    function kolomKeN($kolomAwal, $offset) {
        $panjang = strlen($kolomAwal);
        $kolomAwalIndex = 0;
    
        for ($i = 0; $i < $panjang; $i++) {
            $kolomAwalIndex *= 26;
            $kolomAwalIndex += ord($kolomAwal[$i]) - ord('A') + 1;
        }
    
        $kolomBaruIndex = $kolomAwalIndex + $offset;
    
        $kolomBaru = '';
        while ($kolomBaruIndex > 0) {
            $kolomBaruIndex--; 
            $kolomBaru = chr(($kolomBaruIndex % 26) + ord('A')) . $kolomBaru;
            $kolomBaruIndex = intdiv($kolomBaruIndex, 26);
        }
    
        return $kolomBaru;
    }
}

if (!function_exists('getUserAvatar')) {
    function getUserAvatar($id) {
        $url = URL::to('api/file/users/img_photo_user/' . $id . '/' . time());
        $tumbnailUrl = URL::to('api/tumb-file/users/img_photo_user/' . $id . '/' . time());
        
        return (object) [
            "url" => $url,
            "tumbnail_url" => $tumbnailUrl,
        ];


    }
}

if (!function_exists('isSingleLogin')) {
    function isSingleLogin()
    {
        return env('SINGLE_LOGIN', false);
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission)
    {
        $user = Auth::user();
        if (is_null($user))
            return false;

        $hasPermission = DB::selectOne("SELECT B.role_id FROM users A
        INNER JOIN mapping_roles_permissions B ON B.role_id = A.role_id
        INNER JOIN permissions C ON B.permission_id = C.id AND C.permission_code = ?
        WHERE B.active=true AND A.id = ?", [$permission, $user->id]);

        return !is_null($hasPermission) ? true : ($user->role_id == -1);
    }
}

if (!function_exists('isProduction')) {
    function isProduction()
    {
        return env("APP_ENV") == "production" || env("APP_ENV") == "staging";
    }
}

if (!function_exists('is_blank')) {

    function is_blank($array, $key)
    {
        return isset($array[$key]) ? (is_null($array[$key]) || $array[$key] === "") : true;
    }
}

if (!function_exists('toAlpha')) {

    function toAlpha($data)
    {
        $alphabet =   array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',
            'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
        );
        return $alphabet[$data];
    }
}

if (!function_exists('generatePassword')) {
    function generatePassword($length = 10){
    $chars =  'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_';
  
    $str = '';
    $max = strlen($chars) - 1;
  
    for ($i=0; $i < $length; $i++)
      $str .= $chars[random_int(0, $max)];
  
    return $str;
  }
}

if (!function_exists('tgl_indo')) {
    function tgl_indo($tanggal){
        $bulan = array (
            1 =>   'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );
        $pecahkan = explode('-', $tanggal);
     
        return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
    }
}

if (!function_exists('bln_indo')) {
    function bln_indo($bln){
        $bulan = array (
            1 =>   'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );
     
        return $bulan[(int)$bln];
    }
}

if (!function_exists('bln_tahun_indo')) {
    function bln_tahun_indo($date){
        $bln = date("m", strtotime($date));
        $tahun = date("Y", strtotime($date));
        
        $bulan = array (
            1 =>   'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );
     
        return $bulan[(int)$bln] . " " . $tahun;
    }
}

if (!function_exists('convert_to_server_date')) {
    function convert_to_server_date($dateString, $userTimeZone = null)
    {
        $serverTimeZone = DB::selectOne("show timezone")->TimeZone;

        if (empty($userTimeZone)) {
            $userTimeZone = date_default_timezone_get();
        }
        if (empty($serverTimeZone)) {
            $serverTimeZone = date_default_timezone_get();
        }

        $dt = new DateTime($dateString, new DateTimeZone($serverTimeZone));
        $dt->setTimezone(new DateTimeZone($serverTimeZone));

        return $dt->format("Y-m-d H:i:s");
    }
}

if (!function_exists('formatDecimalWithSeparator')) {
    function formatDecimalWithSeparator($number, $decimals = 2) {
        return number_format($number, $decimals, ',', '.');
    }
}


/*if (!function_exists('logActivity')) {
    function logActivity($taskCode, $id, $activityId = null, $moduleName = null, $reportNumber = null, $sectionId = null)
    {
        $appName = env('APP_NAME') ?? null;
        $permission = DB::selectOne("SELECT*FROM permissions WHERE permission_code=?", [$taskCode]);
        $description = $permission ? $permission->description : $taskCode;

        LogActivity::insert([
            "userid" => Auth::id(),
            "task_code" => $taskCode,
            "description" => $description,
            "report_number" => $reportNumber ?? null,
            "activity_id" => $activityId ?? null,
            "module_name" => $moduleName ?? null,
            "module_id" => $id,
            "section_id" => $sectionId,
            // "job_position_id" => getUserJobPositionId(),
            "ip_address" => Request::ip(),
            "user_agent" => request()->header('User-Agent'),
            "app_name" => $appName,
            "created_by" => Auth::id(),
            "created_at" => now(),
        ]);
    }
}*/

?>