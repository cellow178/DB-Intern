<?php

namespace App\Http\Controllers;

use \App\Models\Banner;
use \App\Models\Mission;
use App\Models\Major;
use App\Models\GlobalConfig;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    // GET Global Config
    public function globalConfig()
    {
        $config = GlobalConfig::first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi belum tersedia.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'profile'   => [
                    'title'       => $config->profile_title,
                    'description' => $config->profile_description,
                    'img_1'       => $config->img_profile_1,
                    'img_2'       => $config->img_profile_2,
                ],
                'video_profile'     => $config->video_profile,
                'school_name'       => $config->school_name,
                'footer'    => [
                    'description'       => $config->footer_description,
                    'motto'             => $config->motto,
                    'school_telephone'  => $config->school_telephone,
                    'school_email'      => $config->school_email,
                    'ig'                => $config->footer_ig,
                    'yt'                => $config->footer_yt,
                    'fb'                => $config->footer_fb,
                    'linkedin'          => $config->footer_linkedin
                ],
            ],
        ]);
    }

    // GET Banners aktif
    public function banner()
    {
        $banners = Banner::where('active', true)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'img_cover', 'url']);

        return response()->json([
            'success' => true,
            'total'   => $banners->count(),
            'data'    => $banners,
        ]);
    }

    // Get Vision-Mission Aktif
    public function visionMission()
    {
        $config = GlobalConfig::first();

        $missions = Mission::where('active', 'true')
            ->orderBy('order', 'asc')
            ->get(['id', 'order', 'content']);

        return response()->json([
            'success'   => true,
            'data'      => [
                'vision'    => $config->school_vision,
                'missions'  => $missions
            ]
        ]);
    }

    // GET Majors aktif (card)
    public function majorCard()
    {
        $majors = Major::where('active', true)
            ->orderBy('major_name', 'asc')
            ->get(['id', 'slug', 'img_logo', 'code', 'major_name', 'summary']);

        return response()->json([
            'success' => true,
            'total'   => $majors->count(),
            'data'    => $majors,
        ]);
    }

    // GET Berita publish (card)
    public function news(Request $request)
    {
        $search = $request->query('search');
        $limit  = $request->query('limit');
        $sort   = $request->query('sort', 'desc');
        $sortBy = $request->query('sort_by', 'created_at');

        $query = News::where('status', 'publish')
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%");
            })
            ->with('createdBy')
            ->orderBy($sortBy, $sort);

        $transform = function ($item) {
            return [
                'id'         => $item->id,
                'slug'       => $item->slug,
                'title'      => $item->title,
                'summary'    => Str::limit(strip_tags($item->content), 120),
                'img_cover'  => $item->img_cover,
                'author'     => $item->createdBy?->fullname ?? 'Admin',
                'created_at' => $item->created_at?->format('d M Y'),
            ];
        };

        // Kalau limit tidak dikirim (null) atau eksplisit 'all', tampilkan semua data
        if ($limit === null || $limit === 'all') {
            $news = $query->get();

            return response()->json([
                'success'     => true,
                'total'       => $news->count(),
                'totalPage'   => 1,
                'currentPage' => 1,
                'data'        => $news->map($transform)->values(),
            ]);
        }

        $news = $query->paginate((int) $limit);

        return response()->json([
            'success'     => true,
            'total'       => $news->total(),
            'totalPage'   => $news->lastPage(),
            'currentPage' => $news->currentPage(),
            'data'        => $news->through($transform)->items(),
        ]);
    }
}
