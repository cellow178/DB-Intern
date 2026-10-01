<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Feedbacks;
use App\Models\Banners;
use App\Models\Events;
use App\Models\Majors;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        try {
            $totalNews = News::count();
            $activeEvents = Events::count();
            $unreadFeedbacks = Feedbacks::count();
            $totalMajors = Majors::count();
            $activeBanners = Banners::count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_news' => $totalNews,
                    'active_events' => $activeEvents,
                    'unread_feedbacks' => $unreadFeedbacks,
                    'total_majors' => $totalMajors,
                    'active_banners' => $activeBanners,
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
