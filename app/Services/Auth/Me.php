<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\DB;
use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class Me extends CoreService
{

    public $transaction = false;
    public $permission = null;

    public function prepare($input)
    {
        return $input;
    }

    public function process($input, $originalInput)
    {
        $userId = Auth::id();
        $user = DB::selectOne("SELECT users.*, employees.telephone, mapping_users_roles.role_id, roles.role_name, employees.active as status_active_employee, employees.resign_date, employees.section_id, employees.job_position_id, toll_sections.section_name, job_positions.name AS job_position_name, employees.ranting_id, CONCAT(m_section_rantings.name, '(KM ', m_section_rantings.km_start, ' - KM ', m_section_rantings.km_end, ')') AS rel_ranting_id
            FROM users 
            LEFT JOIN mapping_users_roles ON users.id=mapping_users_roles.user_id AND mapping_users_roles.active=true
            LEFT JOIN roles ON roles.id=mapping_users_roles.role_id
            LEFT JOIN employees ON employees.id=users.employee_id
            LEFT JOIN job_positions ON job_positions.id=employees.job_position_id
            LEFT JOIN toll_sections ON toll_sections.id=employees.section_id
            LEFT JOIN m_section_rantings ON m_section_rantings.id=employees.ranting_id
            LEFT JOIN role_groups ON role_groups.id=roles.role_group_id
            WHERE users.id=:user_id
            ORDER BY roles.role_group_id ASC", ["user_id" => $userId]);
        if (is_null($user)) {
            throw new CoreException(__("message.403"), 403);
        }
        $sql = "SELECT DISTINCT ON (B.id) B.permission_code FROM mapping_roles_permissions A
                INNER JOIN permissions B ON B.id=A.permission_id
                WHERE A.active=true AND B.active=true AND A.role_id IN 
                (SELECT mapping_users_roles.role_id FROM mapping_users_roles JOIN users ON users.id=mapping_users_roles.user_id WHERE users.id=? AND mapping_users_roles.active=true)";

        $permissionList =   array_map(function ($item) {
            return $item->permission_code;
        }, DB::select($sql, [$user->id]));

        /*
        | ROLES
        */
        $sqlForRolesList = "SELECT B.id, B.role_name, B.role_type
            FROM mapping_users_roles A
            INNER JOIN roles B ON B.id = A.role_id
            INNER JOIN users C ON C.id= A.user_id AND C.id = ? WHERE A.active=true ORDER BY role_id";
        $rolesList = DB::select($sqlForRolesList, [$user->id]);
        
        if (!is_null($user->employee_id)) {
            $user->role_type = "internal";
        } else if (!is_null($user->owner_id)) {
            $user->role_type = "external";
        } else {
            $user->role_type = null;
        }

        foreach (["img_face", "img_photo_user"] as $img) {
            if (!is_null($user->{$img})) {
                $user->{$img} = columnValueToFileObject($img, $user->{$img}, "users", $user->id);
            }
        }

        // REMOVE SOME PROPERTY OF OBJECT
        unset($user->password);
        unset($user->failed_attempt);
        // END REMOVE PROPERTY OF OBJECT
        return [
            "user" => $user,
            "roles" => $rolesList,
            "permissions" => $permissionList,
        ];
    }

    protected function validation()
    {
        return [];
    }
}
