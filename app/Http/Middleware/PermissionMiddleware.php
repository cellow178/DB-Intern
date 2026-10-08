<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\CoreService\CoreException;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = Auth::user();

        if (!$user) {
            throw new CoreException(__("message.403"), 403);
        }

        // Developer & Super Admin
        if (in_array($user->role_id, [-1, 1])) {
            $hasPermission = DB::table('permissions')
                ->where('permission_code', $permission)
                ->where('active', true)
                ->exists();
        } else {
            // Role lain berdasarkan mapping
            $hasPermission = DB::table('mapping_roles_permissions as A')
                ->join('permissions as B', 'B.id', '=', 'A.permission_id')
                ->where('A.role_id', $user->role_id)
                ->where('A.active', true)
                ->where('B.permission_code', $permission)
                ->where('B.active', true)
                ->exists();
        }

        if (!$hasPermission) {
            throw new CoreException(__("message.403"), 403);
        }

        return $next($request);
    }
}
