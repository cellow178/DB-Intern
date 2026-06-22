<?php

namespace App\Services\MappingRolesPermissions;

use App\CoreService\CoreException;
use Illuminate\Support\Facades\DB;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\Auth;

class MappingRolesPermissionsUpdate extends CoreService
{

    public $transaction = true;
    public $permission = null;

    public function prepare($input)
    {
        $permission = "update-mapping-roles-permissions";
        if (!Auth::user()) 
            throw new CoreException(__("message.401"), 401);
        if (!hasPermission($permission))
            throw new CoreException(__("message.forbidden403", ['permission' => $permission]), 403);
        
        return $input;
    }

    public function process($input, $originalInput)
    {
        $roleId = $input["role_id"];
        $permissionId = $input["permission_id"];

        // Cek Permission 
        $permissionExist = DB::selectOne("SELECT * FROM permissions WHERE id=?", [$permissionId]);
        if (!$permissionExist) throw new CoreException("Permission tidak ditemukan");

        $active = filter_var($input["active"], FILTER_VALIDATE_BOOLEAN);
        $valueMapping = $active;
        $mappingExists = DB::selectOne("SELECT * FROM mapping_roles_permissions WHERE role_id = :role_id AND permission_id = :permission_id", ["role_id" => $roleId, "permission_id" => $permissionId]);
        $value = '';
        if($valueMapping == true){
            $value = 'Mengaktifkan';
        }else{
            $value = 'Menonaktifkan';
        }
        if (!$mappingExists) {
            $permissionInput[] = [
                "role_id" => $roleId,
                "permission_id" =>  $permissionId,
                "active" => true,
                "created_by" => Auth::id(),
                "updated_by" => Auth::id(),
                "created_at" => now(),
                "updated_at" => now()
            ];
            DB::table("mapping_roles_permissions")->insert($permissionInput);
        } else {
            DB::table('mapping_roles_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->update([
                    'active' => $valueMapping,
                    'updated_at' => now()
                ]);
        }

        return [
            "message" => __("message.successfullyUpdatePermission", ['value' => $value])
        ];
    }

    protected function validation()
    {
        return [
            "role_id" => "required",
            "permission_id" => "required",
        ];
    }
}
