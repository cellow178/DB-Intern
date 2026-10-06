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

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        try {
            $totalNews = News::where('status', 'publish')->count();
            $activeEvents = Events::where('status', 'publish')->count();
            $unreadFeedbacks = Feedbacks::count();
            $totalMajors = Majors::where('active', 'true')->count();
            $activeBanners = Banners::where('active', 'true')->count();

            $globalConfig = GlobalConfig::first();
            $hasVideo = !empty($globalConfig?->video_profile);
            $hasMap = !empty($globalConfig?->map_embed);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_news' => $totalNews,
                    'active_events' => $activeEvents,
                    'unread_feedbacks' => $unreadFeedbacks,
                    'total_majors' => $totalMajors,
                    'active_banners' => $activeBanners,
                    'has_video' => $hasVideo,
                    'has_map' => $hasMap,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data statistik dashboard.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
