<?php

namespace App\Http\Controllers;

use \App\Models\Banners;
use App\Models\Events;
use App\Models\FeedbacksCategories;
use App\Models\Feedbacks;
use \App\Models\Missions;
use App\Models\GlobalConfig;
use App\Models\Majors;
use App\Models\News;
use App\Models\NewsCategories;
use App\Models\Voting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $img1 = $config->img_profile_1
            ? columnValueToFileObject('img_profile_1', $config->img_profile_1, 'global_config', $config->id)
            : null;

        $img2 = $config->img_profile_2
            ? columnValueToFileObject('img_profile_2', $config->img_profile_2, 'global_config', $config->id)
            : null;

        $highlightVoting = Voting::with(['votingCandidate' => function ($query) {
            $query->where('active', true)->orderBy('order', 'asc');
        }])->where('is_highlight', true)->latest()->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'hero_description' => $config->hero_description,
                'profile'          => [
                    'title'       => $config->profile_title,
                    'description' => $config->profile_description,
                    'img_1'       => $img1?->url,
                    'img_2'       => $img2?->url,
                ],
                'motto'            => $config->motto,
                'video_profile'    => $config->video_profile,
                'school_name'      => $config->school_name,
                'highlight_voting' => $highlightVoting ? [
                    'id'          => $highlightVoting->id,
                    'title'       => $highlightVoting->title,
                    'description' => $highlightVoting->description,
                    'start_date'  => $highlightVoting->start_date?->format('d M Y'),
                    'end_date'    => $highlightVoting->end_date?->format('d M Y'),
                    'candidates'  => $highlightVoting->votingCandidate->map(function ($candidate) {
                        return [
                            'id'          => $candidate->id,
                            'title'       => $candidate->title,
                            'description' => $candidate->description,
                            'img_cover'   => is_array($candidate->img_cover) ? ($candidate->img_cover['url'] ?? null) : $candidate->img_cover,
                            'order'       => $candidate->order,
                        ];
                    }),
                ] : null,
                'footer'           => [
                    'description'      => $config->footer_description,
                    'school_telephone' => $config->school_telephone,
                    'school_email'     => $config->school_email,
                    'ig'               => $config->footer_ig,
                    'yt'               => $config->footer_yt,
                    'fb'               => $config->footer_fb,
                    'linkedin'         => $config->footer_linkedin,
                    'map_embed_url'    => $config->map_embed_url,
                ],
            ],
        ]);
    }

    // GET Banners aktif
    public function banners()
    {
        $banners = Banners::where('active', true)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'img_cover', 'url']);

        $banners->transform(function ($banner) {
            if ($banner->img_cover) {
                $file = columnValueToFileObject(
                    'img_cover',
                    $banner->img_cover,
                    'banners',
                    $banner->id
                );

                $banner->img_cover = $file;
            }

            return $banner;
        });

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

        $missions = Missions::where('active', 'true')
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
        $majors = Majors::where('active', true)
            ->orderBy('id', 'asc')
            ->get(['id', 'slug', 'img_logo', 'code', 'major_name', 'summary']);

        return response()->json([
            'success' => true,
            'total'   => $majors->count(),
            'data'    => $majors,
        ]);
    }

    // GET Events Publish (card, public — no auth)
    public function events(Request $request)
    {
        $search = $request->query('search');
        $limit  = $request->query('limit');
        $sort   = $request->query('sort', 'asc');
        $sortBy = $request->query('sort_by', 'start_date');

        $allowedSorts = ['id', 'title', 'start_date', 'end_date'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'start_date';
        }

        $query = Events::where('status', 'publish')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'ilike', "%{$search}%")
                        ->orWhere('location', 'ilike', "%{$search}%");
                });
            })
            ->with('createdBy')
            ->orderBy($sortBy, $sort);

        $transform = function ($item) {
            // Fungsi pembantu agar tanggal pasti berformat 'd M Y'
            $formatDate = function ($date) {
                if (!$date) return null;
                return $date instanceof \DateTimeInterface
                    ? $date->format('d M Y')
                    : Carbon::parse($date)->format('d M Y');
            };

            return [
                'id'           => $item->id,
                'slug'         => $item->slug,
                'title'        => $item->title,
                'content'      => $item->content,
                'location'     => $item->location,
                'start_date'   => $formatDate($item->start_date),
                'end_date'     => $formatDate($item->end_date),
                'img_cover'    => $item->img_cover,
                'is_highlight' => (bool) $item->is_highlight,
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

        $limit = max(1, (int) $limit);
        $events = $query->paginate($limit);

        return response()->json([
            'success'     => true,
            'total'       => $events->total(),
            'totalPage'   => $events->lastPage(),
            'currentPage' => $events->currentPage(),
            'data'        => $events->through($transform)->items(),
        ]);
    }

    // GET Berita publish
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
            ->with('createdBy', 'category')
            ->orderBy($sortBy, $sort);

        $transform = function ($item) {
            return [
                'id'            => $item->id,
                'slug'          => $item->slug,
                'title'         => $item->title,
                'category_name' => $item->category && $item->category->active ? $item->category->name : null,
                'content'       => $item->content,

                // Menggunakan helper global columnValueToFileObject
                'img_cover'     => $item->img_cover
                    ? columnValueToFileObject('img_cover', $item->img_cover, 'news', $item->id)
                    : null,

                'is_highlight'  => $item->is_highlight,
                'author'        => $item->createdBy?->fullname ?? 'Admin',

                // UBAH BAGIAN INI: Kirim format ISO 8601 agar presisi diparsing Vue (Tanggal & Jam)
                'created_at'    => $item->created_at?->toIso8601String(),
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

        $categories = NewsCategories::select('id', 'name', 'description')
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
                'start_date'      => $item->start_date?->format('d M Y H:i:s'),
                'end_date'        => $item->end_date?->format('d M Y H:i:s'),
                'is_highlight'    => $item->is_highlight
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

        $feedback = Feedbacks::create([
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
                'type'                => $feedback->type ? 'saran' : 'kritik',
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

        $categories = FeedbacksCategories::select('id', 'category_name')
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
