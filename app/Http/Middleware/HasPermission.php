<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HasPermission
{
    public function handle(Request $request, Closure $next, $permissionCode)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Not authorized.'
            ], 401);
        }

        // Developer (-1) & Super Admin (1): lolos selama permission-nya ada & aktif,
        // konsisten dengan PermissionMiddleware, DoLogin, dan Me.
        if (in_array((int) $user->role_id, [-1, 1])) {
            $permission = DB::selectOne(
                "SELECT 1 FROM permissions
                 WHERE permission_code = ? AND active = true",
                [$permissionCode]
            );
        } else {
            $permission = DB::selectOne(
                "SELECT 1
                 FROM mapping_roles_permissions A
                 INNER JOIN permissions B
                    ON B.id = A.permission_id
                 WHERE A.role_id = ?
                   AND A.active = true
                   AND B.permission_code = ?
                   AND B.active = true",
                [
                    $user->role_id,
                    $permissionCode
                ]
            );
        }

        if (!$permission) {
            return response()->json([
                'message' => 'Forbidden. Anda tidak memiliki permission ini.'
            ], 403);
        }

        return $next($request);
    }
}
