<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CoreService\CoreResponse;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;
use App\CoreService\CoreException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Intervention\Image\Facades\Image;

class UploadController extends Controller
{
    public function upload()
    {
        // $req = request()->all();
        // $path = request()->file('file')->store('tmp');
        $file = request()->file('file');
        
        # Validasi Extension
        $allowedExtensions = ['pdf', 'jpeg', 'jpg', 'png', 'gif', 'bmp', 'heic', 'doc', 'docx', 'webp', 'xls', 'xlsx', 'ppt', 'pptx'];
        if (!in_array($file->getClientOriginalExtension(), $allowedExtensions)) return response()->json([
            "success" => false,
            "message" => "Format file tidak diijinkan"
        ], 422);

        $originalname = $file->getClientOriginalName();
        $originalname = time() . "." . $file->getClientOriginalExtension();

        if (Storage::exists("tmp/" . $originalname)) {
            $id = 1;
            $filename = pathinfo(storage_path("tmp/" . $originalname), PATHINFO_FILENAME);
            while (true) {
                $originalname = $filename . "($id)." . $file->getClientOriginalExtension();
                if (!Storage::exists("tmp/" . $originalname))
                    break;
                $id++;
            }
        }
        $path = $file->storeAs('tmp', $originalname);
        $ext = pathinfo(storage_path($path), PATHINFO_EXTENSION);

        $url = URL::to('api/temp-file/' . $originalname . '/' . time()) . '/' . $ext;
        $thumbUrl = URL::to('api/tumb-temp-file/' . $originalname . '/' . time()) . '/' . $ext;

        $result = [
            "url" => $url,
            "tumbnail_url" => $thumbUrl,
            "filename" => $originalname,
            "path" => $path,
            "ext" => $ext
        ];
        return CoreResponse::ok($result);
    }

    public function getTempFile($originalname, $time)
    {
        $data = "tmp/" . $originalname;
        if (Storage::exists($data)) {
            $file = Storage::get($data);
            $type = Storage::mimeType($data);

            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);

            return $response;
        } else {
            $path = "default/notfound.png";
            $file = Storage::get($path);
            $type = Storage::mimeType($path);

            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);

            return $response;
        }
    }

    public function getThumbTempFile($originalname, $time)
    {
        $data = "tmp/" . $originalname;
        if (Storage::exists($data)) {
            $file = Storage::get($data);
            $type = Storage::mimeType($data);

            $response = Image::make($file, 200)->resize(250, 250);
            return $response->response($type);
        } else {
            $path = "default/notfound.png";
            $file = Storage::get($path);
            $type = Storage::mimeType($path);

            $response = Image::make($file, 200)->resize(250, 250);
            $response->header("Content-Type", $type);

            return $response;
        }
    }

    public function getFile($model, $field, $id, $time)
    {
        $classModel = "\\App\\Models\\" . Str::ucfirst(Str::camel($model));
        if (!class_exists($classModel))
            throw new CoreException("Not found", 404);

        if (!$classModel::FILEROOT)
            throw new CoreException("Not found", 404);

        $sql = "SELECT A." . $field . " FROM " . $classModel::TABLE . " A WHERE A.id = :id";
        $params = ["id" => $id];

        $fileName =  DB::selectOne($sql, $params)->$field;

        $path  = $classModel::FILEROOT . '/';
        $data = $fileName;

        if (isset($data) && Storage::exists($data)) {
            $file = Storage::get($data);
            $type = Storage::mimeType($data);

            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);

            return $response;
        } else {
            if ($field == "img_photo_user") {
                $path = "default/avatar.png";
            } else {
                $path = "default/notfound.png";
            }
            $file = Storage::get($path);
            $type = Storage::mimeType($path);

            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);

            return $response;
        }
    }

    public function getTumbnailFile($model, $field, $id, $time)
    {
        $classModel = "\\App\\Models\\" . Str::ucfirst(Str::camel($model));
        if (!class_exists($classModel))
            throw new CoreException("Not found", 404);

        if (!$classModel::FILEROOT)
            throw new CoreException("Not found", 404);

        $sql = "SELECT A." . $field . " FROM " . $classModel::TABLE . " A WHERE A.id = :id";
        $params = ["id" => $id];

        $fileName =  DB::selectOne($sql, $params)->$field;

        $path  = $classModel::FILEROOT . '/';
        $data = $fileName;
        if (Storage::exists($data)) {
            $file = Storage::get($data);
            $type = Storage::mimeType($data);

            $response = Image::make($file, 200)->resize(250, 250);
            return $response->response($type);
        } else {
            $path = "default/notfound.png";
            $file = Storage::get($path);
            $type = Storage::mimeType($path);

            $response = Image::make($file, 200)->resize(250, 250);
            return $response->response($type);
        }
    }

    public function downloadFile($model, $field, $id, $time)
    {
        $classModel = "\\App\\Models\\" . Str::ucfirst(Str::camel($model));
        if (!class_exists($classModel))
            throw new CoreException("Not found", 404);

        if (!$classModel::FILEROOT)
            throw new CoreException("Not found", 404);

        $sql = "SELECT A." . $field . " FROM " . $classModel::TABLE . " A WHERE A.id = :id";
        $params = ["id" => $id];

        $fileName =  DB::selectOne($sql, $params)->$field;
        if (isset($fileName) && Storage::exists($fileName)) {
            // $path = Storage::get($fileName);
            return Storage::disk('local')->download($fileName);
        } else {
            return response()->json([
                'message' => 'File tidak ditemukan.'
            ], 404);
        }
    }

}
