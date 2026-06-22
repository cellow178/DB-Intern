<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\DB;
use App\CoreService\CoreException;
use App\CoreService\CoreService;
use App\CoreService\CustomException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Webpatser\Uuid\Uuid;

class DoLogin extends CoreService
{

    public $transaction = false;
    public $permission = null;
    public $noAuth = true;

    public function prepare($input)
    {
        return $input;
    }

    public function process($input, $originalInput)
    {
        #
        $credentials = [
            "username" => $input["username"],
            "password" => $input["password"]
        ];

        // CEK USER ID
        $user = DB::selectOne("SELECT users.*, roles.role_code, roles.role_name
            FROM users 
            LEFT JOIN roles ON roles.id=users.role_id
            WHERE users.email=:username OR users.username=:username", ["username" => $input['username']]);

        if (empty($user)) throw new CoreException(__("message.userNotFound", ['username' => $input["username"]]), 422);

        // if ($user->failed_attempt >= 10) throw new CoreException(__("message.tooManyAttempt"), 422);

        $fieldType = filter_var($credentials["username"], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Config::get("auth.defaults.guard") == "web") {
            if ($token = !Auth::attempt(array($fieldType => $input['username'], 'password' => $input['password']))) {
                // failed login attempt
                // DB::statement("UPDATE users SET failed_attempt=failed_attempt+1 WHERE username=:username", ["username" => $user->username]);

                throw new CoreException(__("message.loginCredentialFalse"), 422);
            }
            Auth::loginUsingId($user->id);
            $token = null;

            // DB::statement("UPDATE users SET failed_attempt=0 WHERE username=:username", ["username" => $user->username]);
        } else {
            $session_token = Uuid::generate()->string;
            if ($token = Auth::claims(['session_id' => $session_token])->attempt(array($fieldType => $input['username'], 'password' => $input['password']))) {

                JWTAuth::setToken($token);

                // DB::statement("UPDATE users SET failed_attempt=0, last_login_at=now() WHERE username=:username", ["username" => $user->username]);
            } else {
                // DB::statement("UPDATE users SET failed_attempt=0 WHERE username=:username", ["username" => $user->username]);

                throw new CoreException(__("message.loginCredentialFalse"), 422);
            }
        }

        $data_user_before_login["fullname"] = $user->fullname;
        $data_user_before_login["username"] = $user->username;
        $data_user_before_login["email"] = $user->email;
        $data_user_before_login["rel_role_id"] = $user->role_name;
        $data_user_before_login["status_code"] = $user->status_code;
        $response = [];

        if ($user->status_code == 'email_unverified') {
            $response["message"] = __("message.userEmailNotVerifiedYet", ['email' => $user->email]);
            $response["data_user_before_login"] = $data_user_before_login;
            throw new CoreException(__("message.userEmailNotVerifiedYet", ['email' => $user->email]), 422, $data_user_before_login);
        }

        if ($user->status_code == 'email_verified') {
            $response["message"] = __("message.userEmailVerifiedWaitingApproval", ['username' => $user->username]);
            $response["data_user_before_login"] = $data_user_before_login;
            throw new CoreException(__("message.userEmailVerifiedWaitingApproval", ['username' => $user->username]), 422, $data_user_before_login);
        }

        if ($user->status_code == 'user_rejected') {
            $response["message"] = __("message.userRejectedByAdmin", ['username' => $user->username]);
            $response["data_user_before_login"] = $data_user_before_login;
            throw new CoreException(__("message.userRejectedByAdmin", ['username' => $user->username]), 422, $data_user_before_login);
        }

        if ($user->status_code == 'user_nonactive') {
            $response["message"] = __("message.userNotActive", ['username' => $user->username]);
            $response["data_user_before_login"] = $data_user_before_login;
            throw new CoreException(__("message.userNotActive", ['username' => $user->username]), 422, $data_user_before_login);
        }

        if ($user->status_code != 'user_active') {
            $response["message"] = __("message.userNotActive", ['username' => $user->username]);
            $response["data_user_before_login"] = $data_user_before_login;
            throw new CoreException(__("message.userNotActive", ['username' => $user->username]), 422, $data_user_before_login);
        }

        /*
        | PERMISSION
        */
        $params = [];
        if ($user->role_id == -1) {
            $sql = "SELECT B.permission_code FROM permissions B WHERE B.active=true";
        } else {
            $sql = "SELECT B.permission_code FROM mapping_roles_permissions A
                    INNER JOIN permissions B ON B.id = A.permission_id
                    INNER JOIN users C ON C.role_id = A.role_id AND C.id = ? WHERE A.active=1";
            $params[] = $user->id;
        }

        $permissionList =  array_map(function ($item) {
            return $item->permission_code;
        }, DB::select($sql, $params));

        // foreach (["img_photo_user"] as $img) {
        //     if (!is_null($user->{$img})) {
        //         $user->{$img} = columnValueToFileObject($img, $user->{$img}, "users", $user->id);
        //     }
        // }

        // REMOVE SOME PROPERTY OF OBJECT
        unset($user->password);
        unset($user->failed_attempt);
        // END REMOVE PROPERTY OF OBJECT
        return [
            "success" => true,
            "user" => $user,
            "token" => $token,
            "permissions" => $permissionList,
            "message" => __("message.loginSuccess"),
        ];
    }

    protected function validation()
    {
        return [
            "username" => "required",
            "password" => "required",
            // "device" => "nullable|in:andoid,ios,web"
        ];
    }
}
