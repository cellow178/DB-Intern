<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $task)
    {
        $userId = Auth::id();
        $permission = DB::selectOne("SELECT 1 FROM users A
            INNER JOIN mapping_roles_tasks B ON B.role_id = A.role_id
            INNER JOIN tasks C ON B.task_id = C.id AND C.task_code = ?
        WHERE A.id = ?", [$task, $userId]);

        if (is_null($permission)) {
            return response()->json(["message" => __("message.403")]);
        }

        return $next($request);
    }
}
