<?php

namespace App\CoreService;

use Exception;
use App\CoreService\CoreException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallService
{

    public static function execute($serviceName, $input)
    {
        
        try {
            $object = $object = app()->make($serviceName);

            if (!(isset($object->noAuth) ? $object->noAuth : false)) {
                if (!Auth::user()) {
                    throw new CoreException(__("message.401"), 401);
                }
            }

            if (isset($object->task) && ($object->task != null)) {
                if (!hasPermission($object->task))
                    throw new CoreException("Forbidden", 403);
            }

            // begin transaction
            if ($object->transaction !== null && $object->transaction !== false)
                DB::beginTransaction();
            $input["session"] = [
                "datetime" => date("YmdHis"),
                "user_id" => Auth::id()
            ];
            $result = $object->execute($input);

            if ($object->transaction !== null && $object->transaction !== false)
                DB::commit();

            return CoreResponse::ok($result);
        } catch (CoreException $ex) {
            if ($object->transaction !== null && $object->transaction !== false)
                DB::rollback();

            return CoreResponse::fail($ex);
        } catch (Exception $ex) {
            Log::debug($ex->getMessage());
            if (isset($object) && $object->transaction !== null && $object->transaction !== false)
                DB::rollback();

            $result["success"] = false;
            $result["error_message"] = $ex->getMessage();
            return response()->json($result, 500);
        }
    }

    public static function run($serviceName, $input)
    {
        try {
            $object = $object = app()->make($serviceName);

            // begin transaction
            if ($object->transaction !== null && $object->transaction !== false)
                DB::beginTransaction();
            $result = $object->execute($input);

            if ($object->transaction !== null && $object->transaction !== false)
                DB::commit();

            return CoreResponse::ok($result);
        } catch (CoreException $ex) {
            if ($object->transaction !== null && $object->transaction !== false)
                DB::rollback();

            return CoreResponse::fail($ex);
        } catch (Exception $ex) {
            if (isset($object) && $object->transaction !== null && $object->transaction !== false)
                DB::rollback();

            $result["success"] = false;
            $result["error_message"] = $ex->getMessage();
            return response()->json($result, 500);
        }
    }

    public static function call($serviceName, $input)
    {
        try {
            $object = $object = app()->make($serviceName);
            return $object->execute($input);
        } catch (CoreException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
}
