<?php

namespace App\Http\Controllers;

use App\CoreService\CallService;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class NewsController extends Controller
{
    // GET List
    public function index(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'news';
        $input['limit'] = $input['limit'] ?? 10;

        $input['sort_by'] = $input['sort_by'] ?? 'updated_at';
        $input['sort']    = $input['sort'] ?? 'desc';

        return CallService::run('Get', $input);
    }

    // GET Dataset
    public function dataset(Request $request)
    {
        $input = $request->all();
        $input['model']   = 'news';
        $input['sort_by'] = $input['sort_by'] ?? 'updated_at';
        $input['sort']    = $input['sort'] ?? 'desc';

        return CallService::run('Dataset', $input);
    }

    // GET Detail (Show) by ID
    public function show(int $id)
    {
        return CallService::run('Find', [
            'id'    => $id,
            'model' => 'news',
        ]);
    }

    // POST Create
    public function create(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'news';

        return CallService::run('Add', $input);
    }

    // PUT Update
    public function update(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'news';

        // Deteksi jika gambar dihapus/dikosongkan dari frontend
        if (empty($input['img_cover']) || $input['img_cover'] === 'null') {

            // 1. Force update langsung ke DB mengabaikan CallService
            News::where('id', $input['id'])->update(['img_cover' => null]);

            // 2. Buang key img_cover agar CallService tidak mencoba memproses file yang tidak ada
            unset($input['img_cover']);
        }

        return CallService::run('Edit', $input);
    }

    //POST Update highlight news
    public function updateHighlight(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:news,id'],
            ], [
                'id.required' => 'ID berita wajib diisi.',
                'id.integer'  => 'ID berita harus berupa angka.',
                'id.exists'   => 'Berita tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $news = News::find($validated['id']);

        if ($news->status !== 'publish') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya berita berstatus publish yang dapat dijadikan highlight.',
            ], 422);
        }

        $newHighlightStatus = !$news->is_highlight;

        if ($newHighlightStatus) {
            News::where('id', '!=', $news->id)
                ->where('is_highlight', true)
                ->update(['is_highlight' => false]);
        }

        $news->update([
            'is_highlight' => $newHighlightStatus,
            'updated_by'   => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Berita '{$news->title}' berhasil " . ($newHighlightStatus ? 'dijadikan' : 'dibatalkan dari') . ' highlight.',
        ]);
    }


    // DELETE
    public function destroy(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'news';

        return CallService::run('Delete', $input);
    }
}
