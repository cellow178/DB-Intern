<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class MissionsController extends Controller
{
    // GET Mission list
    public function index(Request $request)
    {
        $statusCode = $request->query('status_code');

        $missions = Mission::with(['createdBy', 'updatedBy'])
            ->when($statusCode !== null, function ($query) use ($statusCode) {
                $query->where('status_code', filter_var($statusCode, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'total'   => $missions->count(),
            'data'    => $missions->map(function ($item) {
                return [
                    'id'                  => $item->id,
                    'content'             => $item->content,
                    'order'               => $item->order,
                    'status_code'         => $item->status_code,
                    'created_by_fullname' => $item->createdBy?->fullname,
                    'updated_by_fullname' => $item->updatedBy?->fullname,
                ];
            }),
        ]);
    }

    // GET Mission dataset/lookup

    public function dataset(Request $request)
    {
        $missions = Mission::select('id', 'content')
            ->when($request->query('status_code') !== null, function ($query) use ($request) {
                $query->where('status_code', filter_var($request->query('status_code'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'total'   => $missions->count(),
            'data'    => $missions,
        ]);
    }

    // GET Mission detail (Show) by ID
    public function show(int $id)
    {
        $mission = Mission::with(['createdBy', 'updatedBy'])->find($id);

        if (!$mission) {
            return response()->json([
                'success' => false,
                'message' => 'Misi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $mission->id,
                'content'             => $mission->content,
                'order'               => $mission->order,
                'status_code'         => $mission->status_code,
                'created_by_fullname' => $mission->createdBy?->fullname,
                'created_at'          => $mission->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $mission->updatedBy?->fullname,
                'updated_at'          => $mission->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Create mission
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'content' => ['required', 'string'],
                'order'   => ['required', 'integer', 'min:1', Rule::unique('missions', 'order')],
            ], [
                'content.required' => 'Isi misi wajib diisi.',
                'order.required'   => 'Urutan misi wajib diisi.',
                'order.integer'    => 'Urutan misi harus berupa angka.',
                'order.min'        => 'Urutan misi minimal 1.',
                'order.unique'     => 'Urutan misi sudah digunakan, silakan pilih urutan lain.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $mission = Mission::create([
            'content'     => $validated['content'],
            'order'       => $validated['order'],
            'status_code' => true,
            'created_by'  => Auth::id(),
            'updated_by'  => Auth::id(),
        ]);

        $mission->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $mission->id,
                'content'             => $mission->content,
                'order'               => $mission->order,
                'status_code'         => $mission->status_code,
                'created_by_fullname' => $mission->createdBy?->fullname,
                'created_at'          => $mission->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $mission->updatedBy?->fullname,
                'updated_at'          => $mission->updated_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // PUT Update mission
    public function update(Request $request)
    {
    try {
        $validated = $request->validate([
            'id'      => ['required', 'integer', 'exists:missions,id'],
            'content' => ['required', 'string'],
            'order'   => [
                'required',
                'integer',
                'min:1',
                Rule::unique('missions', 'order')->ignore($request->input('id')),
            ],
        ], [
            'id.required'      => 'ID misi wajib diisi.',
            'id.exists'        => 'Misi tidak ditemukan.',
            'content.required' => 'Isi misi wajib diisi.',
            'order.required'   => 'Urutan misi wajib diisi.',
            'order.integer'    => 'Urutan misi harus berupa angka.',
            'order.min'        => 'Urutan misi minimal 1.',
            'order.unique'     => 'Urutan misi sudah digunakan, silakan pilih urutan lain.',
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal.',
            'errors'  => $e->errors(),
        ], 422);
    }

        $mission = Mission::find($validated['id']);

        $mission->update([
            'content'    => $validated['content'] ?? $mission->content,
            'order'      => $validated['order'] ?? $mission->order,
            'updated_by' => Auth::id(),
        ]);

        $mission->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Misi berhasil diperbarui.',
            'data'    => [
                'id'                  => $mission->id,
                'content'             => $mission->content,
                'order'               => $mission->order,
                'status_code'         => $mission->status_code,
                'created_by_fullname' => $mission->createdBy?->fullname,
                'created_at'          => $mission->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $mission->updatedBy?->fullname,
                'updated_at'          => $mission->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Toggle status (aktif/nonaktif)
    public function updateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:missions,id'],
            ], [
                'id.required' => 'ID misi wajib diisi.',
                'id.integer'  => 'ID misi harus berupa angka.',
                'id.exists'   => 'Misi tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $mission = Mission::find($validated['id']);

        $mission->update([
            'status_code' => !$mission->status_code,
            'updated_by'  => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status misi berhasil diperbarui.',
            'data'    => [
                'id'          => $mission->id,
                'status_code' => $mission->status_code,
            ],
        ]);
    }

    // DELETE Delete mission
    public function destroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:missions,id'],
            ], [
                'id.required' => 'ID misi wajib diisi.',
                'id.integer'  => 'ID misi harus berupa angka.',
                'id.exists'   => 'Misi tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $mission = Mission::find($validated['id']);
        $mission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Misi berhasil dihapus.',
        ]);
    }
}