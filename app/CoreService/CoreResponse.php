<?php

namespace App\CoreService;

use Illuminate\Support\Facades\Log;

class CoreResponse
{

    public static function ok($output, $message = "")
    {
        $output["success"] = true;
        return response()->json($output, 200);
    }

    public static function fail($ex)
    {
        $result["success"] = false;

        if (!empty($ex->getErrorMessage()) && !is_null($ex->getErrorMessage())) {
            if (str_contains($ex->getErrorMessage(), "SQLSTATE")) {
                $result["message"] = (__("message.databaseQueryError"));
                // $result["message"] = $ex->getErrorMessage();

                if (env('APP_DEBUG') == true) {
                    $result["message"] = $ex->getErrorMessage();
                } else {
                    $result["message"] = (__("message.databaseQueryError"));
                }

                $result["data"] = $ex->getErrorMessage();
            } else {
                $result["message"] = $ex->getErrorMessage();
                $result["data"] = $ex->getErrorList();
            }
        }

        return response()->json($result, $ex->getErrorCode());
    }

    public static function failWithData($ex)
    {
        $result["success"] = false;
        if (!empty($ex->getErrorMessage()) && !is_null($ex->getErrorMessage())) {
            $result["message"] = $ex->getErrorMessage();
        }
        Log::debug("fail with data");

        return response()->json($result, $ex->getErrorCode());
    }

    public static function error($ex)
    {
        $result["success"] = false;
        if (!empty($ex->getErrorMessage()) && !is_null($ex->getErrorMessage())) {
            $result["message"] = $ex->getErrorMessage();
        }

        $result["error_code"] = $ex->getErrorCode();
        return response()->json($result, $ex->getErrorCode());
    }
}
