<?php

namespace App\Http\Controllers;

use \App\Models\Banner;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\FeedbackCategory;
use \App\Models\Mission;
use App\Models\Major;
use App\Models\GlobalConfig;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Voting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi belum tersedia.',
            ], 404);
        }

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
            ->orderBy('id', 'asc')
            ->get(['id', 'slug', 'img_logo', 'code', 'major_name', 'summary']);

        return response()->json([
            'success' => true,
            'total'   => $majors->count(),
            'data'    => $majors,
        ]);
    }

    // GET Event Publish (card)
    public function event(Request $request)
    {
        $search = $request->query('search');
        $limit  = $request->query('limit');
        $sort   = $request->query('sort', 'asc');
        $sortBy = $request->query('sort_by', 'start_date');

        $query = Event::where('status', 'publish')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'ilike', "%{$search}%")
                        ->orWhere('location', 'ilike', "%{$search}%");
                });
            })
            ->with('createdBy')
            ->orderBy($sortBy, $sort);

        $transform = function ($item) {
            return [
                'id'           => $item->id,
                'slug'         => $item->slug,
                'title'        => $item->title,
                'content'      => $item->content,
                'location'     => $item->location,
                'start_date'   => $item->start_date?->format('d M Y'),
                'end_date'     => $item->end_date?->format('d M Y'),
                'img_cover'    => $item->img_cover,
                'is_highlight' => $item->is_highlight,
                'author'       => $item->createdBy?->fullname ?? 'Admin',
            ];
        };

        // Kalau limit tidak dikirim (null) atau eksplisit 'all', tampilkan semua data
        if ($limit === null || $limit === 'all') {
            $events = $query->get();

            return response()->json([
                'success'     => true,
                'total'       => $events->count(),
                'totalPage'   => 1,
                'currentPage' => 1,
                'data'        => $events->map($transform)->values(),
            ]);
        }

        $events = $query->paginate((int) $limit);

        return response()->json([
            'success'     => true,
            'total'       => $events->total(),
            'totalPage'   => $events->lastPage(),
            'currentPage' => $events->currentPage(),
            'data'        => $events->through($transform)->items(),
        ]);
    }

    // GET Berita publish (card)
    public function news(Request $request)
    {
        $search     = $request->query('search');
        $categoryId = $request->query('category_id');
        $limit      = $request->query('limit');
        $sort       = $request->query('sort', 'desc');
        $sortBy     = $request->query('sort_by', 'created_at');

        $query = News::where('status', 'publish')
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%");
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->with('createdBy')
            ->orderBy($sortBy, $sort);

        $transform = function ($item) {
            return [
                'id'         => $item->id,
                'slug'       => $item->slug,
                'title'      => $item->title,
                'summary'    => $item->content,
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

    public function newsCategories(Request $request)
    {
        $search = $request->query('search');

        $categories = NewsCategory::select('id', 'name', 'description')
            ->where('active', true)
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'total'   => $categories->count(),
            'data'    => $categories,
        ]);
    }

    // GET Voting aktif (card)
    public function voting(Request $request)
    {
        $search = $request->query('search');
        $limit  = $request->query('limit');
        $sort   = $request->query('sort', 'asc');
        $sortBy = $request->query('sort_by', 'end_date');

        $query = Voting::where('active', true)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            })
            ->withCount('votingCandidate')
            ->orderBy($sortBy, $sort);

        $transform = function ($item) {
            return [
                'id'              => $item->id,
                'slug'            => $item->slug,
                'title'           => $item->title,
                'description'     => $item->description,
                'img_cover'       => $item->img_cover,
                'start_date'      => $item->start_date?->format('d M Y H:i'),
                'end_date'        => $item->end_date?->format('d M Y H:i'),
                'is_highlight'    => $item->is_highlight,
                'candidate_count' => $item->voting_candidate_count,
            ];
        };

        // Kalau limit tidak dikirim (null) atau eksplisit 'all', tampilkan semua data
        if ($limit === null || $limit === 'all') {
            $votings = $query->get();

            return response()->json([
                'success'     => true,
                'total'       => $votings->count(),
                'totalPage'   => 1,
                'currentPage' => 1,
                'data'        => $votings->map($transform)->values(),
            ]);
        }

        $votings = $query->paginate((int) $limit);

        return response()->json([
            'success'     => true,
            'total'       => $votings->total(),
            'totalPage'   => $votings->lastPage(),
            'currentPage' => $votings->currentPage(),
            'data'        => $votings->through($transform)->items(),
        ]);
    }

    // POST Create Feedback
    public function createFeedback(Request $request)
    {
        try {
            $validated = $request->validate([
                'sender_name' => ['nullable', 'string', 'max:100'],
                'type'        => ['required', 'boolean'],
                'category_id' => ['required', 'integer', 'exists:feedbacks_categories,id'],
                'message'     => ['required', 'string'],
                'is_anonymous' => ['nullable', 'boolean']
            ], [
                'sender_name.max'      => 'Nama pengirim maksimal 100 karakter.',
                'type.required'        => 'Jenis feedback wajib diisi.',
                'type.boolean'         => 'Jenis feedback harus berupa true (saran) atau false (kritik).',
                'category_id.required' => 'Kategori feedback wajib diisi.',
                'category_id.exists'   => 'Kategori feedback tidak ditemukan.',
                'message.required'     => 'Pesan feedback wajib diisi.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $isAnonymous = filter_var($validated['is_anonymous'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $feedback = Feedback::create([
            'sender_name' => $validated['sender_name'] ?? null,
            'type'        => filter_var($validated['type'], FILTER_VALIDATE_BOOLEAN),
            'category_id' => $validated['category_id'],
            'message'     => $validated['message'],
            'created_by'  => $isAnonymous ? null : Auth::id(),
        ]);

        $feedback->load(['category', 'createdBy']);

        return response()->json([
            'success' => true,
            'message' => 'Feedback berhasil dikirim.',
            'data'    => [
                'id'                  => $feedback->id,
                'sender_name'         => $feedback->sender_name ?? 'Anonim',
                'type'                => $feedback->type,
                'category_id'         => $feedback->category_id,
                'category_name'       => $feedback->category?->category_name,
                'message'             => $feedback->message,
                'created_by_fullname' => $feedback->createdBy?->fullname,
                'created_at'          => $feedback->created_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // GET Feedback Categories Dataset
    public function feedbackCategoriesDataset(Request $request)
    {
        $search = $request->query('search');
        $limit  = $request->query('limit');

        $categories = FeedbackCategory::select('id', 'category_name')
            ->where('active', true)
            ->when($search, function ($query) use ($search) {
                $query->where('category_name', 'ilike', "%{$search}%");
            })
            ->orderBy('category_name')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }
}
