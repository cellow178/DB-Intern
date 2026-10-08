<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Feedbacks;
use App\Models\Banners;
use App\Models\Events;
use App\Models\Majors;
use App\Models\GlobalConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized.',
                ], 401);
            }

            // Developer dan Super Admin memiliki semua permission
            $isDeveloper = $user->role_id === -1;
            $isSuperAdmin = $user->role_id === 1;

            // Ambil permission user
            if ($isDeveloper || $isSuperAdmin) {
                $permissions = DB::table('permissions')
                    ->where('active', true)
                    ->pluck('permission_code')
                    ->toArray();
            } else {
                $permissions = DB::table('mapping_roles_permissions as A')
                    ->join('permissions as B', 'B.id', '=', 'A.permission_id')
                    ->where('A.role_id', $user->role_id)
                    ->where('A.active', true)
                    ->where('B.active', true)
                    ->pluck('B.permission_code')
                    ->toArray();
            }

            $hasPermission = function (string $permission) use ($permissions): bool {
                return in_array($permission, $permissions, true);
            };

            $data = [
                'total_news' => null,
                'active_events' => null,
                'unread_feedbacks' => null,
                'total_majors' => null,
                'active_banners' => null,
                'has_video' => null,
                'has_map' => null,
            ];

            // Berita
            if ($hasPermission('view-news')) {
                $data['total_news'] = News::where('status', 'publish')->count();
            }

            // Event
            if ($hasPermission('view-events')) {
                $data['active_events'] = Events::where('status', 'publish')->count();
            }

            // Kritik & Saran
            if ($hasPermission('view-feedbacks')) {
                $data['unread_feedbacks'] = Feedbacks::count();
            }

            // Jurusan
            if ($hasPermission('view-majors')) {
                $data['total_majors'] = Majors::where('active', true)->count();
            }

            // Banner
            if ($hasPermission('view-banners')) {
                $data['active_banners'] = Banners::where('active', true)->count();
            }

            // Global Config
            if ($hasPermission('show-global-config')) {
                $globalConfig = GlobalConfig::first();

                $data['has_video'] = !empty($globalConfig?->video_profile);
                $data['has_map'] = !empty($globalConfig?->map_embed);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data statistik dashboard.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
