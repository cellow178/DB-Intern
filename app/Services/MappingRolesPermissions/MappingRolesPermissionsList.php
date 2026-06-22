<?php

namespace App\Services\MappingRolesPermissions;

use Illuminate\Support\Facades\DB;
use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MappingRolesPermissionsList extends CoreService
{

    public $transaction = false;
    public $permission = null;

    public function prepare($input)
    {
        $modelPermission = 'permissions';
        $permission = 'view-mapping-roles-permissions';

        if (!Auth::user()) 
            throw new CoreException(__("message.401"), 401);
        if (!hasPermission($permission))
            throw new CoreException(__("message.forbidden403", ['permission' => $permission]), 403);
            
        $classModelPermission = "\\App\\Models\\" . Str::ucfirst(Str::camel($modelPermission));
        if (!class_exists($classModelPermission))
            throw new CoreException(__("message.model404", ['model' => $modelPermission]), 404);

        $input["class_model_permission"] = $classModelPermission;
        return $input;
    }

    public function process($input, $originalInput)
    {
        $classModelPermission = $input["class_model_permission"];
        $sortBy = $classModelPermission::TABLE . ".permission_group, " . $classModelPermission::TABLE . ".id";
        $sort = "ASC";


        $condition = "WHERE permissions.active=true";
        $params = [];

        if (!is_blank($input, "search")) {

            $searchableList = ["permission_code", "permission_name", "description"];

            $searchableList = array_map(function ($item) {
                return "UPPER($item) ILIKE :search";
            }, $searchableList);
        } else {
            $searchableList = [];
        }

        //// FILTER permission
        $filterListPermission = [];

        foreach ($classModelPermission::FIELD_FILTERABLE as $filter => $operator) {
            if (!is_blank($input, $filter)) {
                if ($filter == 'active') {
                    $params[$filter] = $input[$filter];
                } else {
                    $filterListPermission[] = " AND " . $classModelPermission::TABLE . "." . $filter .  " " . $operator["operator"] . " :$filter";
                    $params[$filter] = $input[$filter];
                }
            }
        }
        if (!is_blank($input, "active") and !is_blank($input, "role_id")) {
            $active = filter_var($input["active"], FILTER_VALIDATE_BOOLEAN);
            if ($active == true) {
                $condition = $condition . " AND EXISTS (SELECT 1 FROM mapping_roles_permissions WHERE permission_id = permissions.id AND active = :active AND role_id = " . $input["role_id"] . " )";
            } else {
                $condition = $condition . " AND NOT EXISTS (SELECT 1 FROM mapping_roles_permissions WHERE permission_id = permissions.id AND role_id = " . $input["role_id"] . " ) " . " OR EXISTS (SELECT 1 FROM mapping_roles_permissions WHERE permission_id = permissions.id AND active = :active AND role_id = " . $input["role_id"] . " )";
            }
        };

        if (count($searchableList) > 0 && !is_blank($input, "search"))
            $params["search"] = "%" . strtoupper($input["search"] ?? "") . "%";

        $limit = $input["limit"] ?? 10;
        $offset = $input["offset"] ?? 0;
        if (!is_null($input["page"] ?? null)) {
            $offset = $limit * ($input["page"] - 1);
        }
        $sql = "SELECT permissions.id, permissions.permission_code, permissions.permission_name, permissions.permission_group, permissions.description, COALESCE(mapping_roles_permissions.active, false) AS active 
            FROM permissions 
            LEFT JOIN mapping_roles_permissions ON permissions.id=mapping_roles_permissions.permission_id AND mapping_roles_permissions.role_id='" . $input['role_id'] . "' " . 
            $condition . " " . (count($searchableList) > 0 ? " AND (" . implode(" OR ", $searchableList) . ")" : "") .
            implode("\n", $filterListPermission) .
            " ORDER BY " . $sortBy . " " . $sort . " LIMIT $limit OFFSET $offset ";
        $sqlForCountPermission = "SELECT COUNT(1) AS total FROM permissions " . $condition .
            (count($searchableList) > 0 ? " AND (" . implode(" OR ", $searchableList) . ")" : "") .
            implode("\n", $filterListPermission);

        $result = DB::select($sql, $params);
        $total = DB::selectOne($sqlForCountPermission, $params)->total;

        //
        $totalPage = ceil($total / $limit);

        return [
            "data" => $result,
            "total" => $total,
            "totalPage" => $totalPage
        ];
    }

    protected function validation()
    {
        return [
            "role_id" => "required"
        ];
    }
}
