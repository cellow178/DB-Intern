<?php

namespace App\Http\Controllers;

use App\Models\GlobalConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class GlobalConfigController extends Controller
{
    // GET Global Config detail
    public function show()
    {
        $config = GlobalConfig::with(['createdBy', 'updatedBy'])->first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi global belum tersedia.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatDetail($config),
        ]);
    }

    // PUT Update Global Config
    public function update(Request $request)
    {
        $config = GlobalConfig::first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi global belum tersedia.',
            ], 404);
        }

        try {
            $validated = $request->validate([
                'profile_title'       => ['required', 'string'],
                'profile_description' => ['required', 'string'],
                'img_profile_1'       => ['required', 'string'],
                'img_profile_2'       => ['required', 'string'],
                'school_vision'       => ['required', 'string'],
                'video_profile'       => ['required', 'string'],
                'school_name'         => ['required', 'string', 'max:150'],
                'footer_description'  => ['required', 'string'],
                'motto'               => ['required', 'string', 'max:100'],
                'school_telephone'    => ['required', 'string', 'max:150'],
                'school_email'        => ['required', 'email'],
                'footer_ig'           => ['nullable', 'string'],
                'footer_yt'           => ['nullable', 'string'],
                'footer_fb'           => ['nullable', 'string'],
                'footer_linkedin'     => ['nullable', 'string'],
            ], [
                'profile_title.required'       => 'Judul profil wajib diisi.',
                'profile_description.required' => 'Deskripsi profil wajib diisi.',
                'img_profile_1.required'       => 'Gambar profil 1 wajib diisi.',
                'img_profile_2.required'       => 'Gambar profil 2 wajib diisi.',
                'school_vision.required'       => 'Visi sekolah wajib diisi.',
                'video_profile.required'       => 'Video profil wajib diisi.',
                'school_name.required'         => 'Nama sekolah wajib diisi.',
                'school_name.max'              => 'Nama sekolah maksimal 150 karakter.',
                'footer_description.required'  => 'Deskripsi footer wajib diisi.',
                'motto.required'               => 'Motto wajib diisi.',
                'motto.max'                     => 'Motto maksimal 100 karakter.',
                'school_telephone.required'    => 'Telepon sekolah wajib diisi.',
                'school_telephone.max'         => 'Telepon sekolah maksimal 150 karakter.',
                'school_email.required'        => 'Email sekolah wajib diisi.',
                'school_email.email'           => 'Format email tidak valid.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $config->update([
            'profile_title'       => $validated['profile_title'],
            'profile_description' => $validated['profile_description'],
            'img_profile_1'       => $validated['img_profile_1'],
            'img_profile_2'       => $validated['img_profile_2'],
            'school_vision'       => $validated['school_vision'],
            'video_profile'       => $validated['video_profile'],
            'school_name'         => $validated['school_name'],
            'footer_description'  => $validated['footer_description'],
            'motto'               => $validated['motto'],
            'school_telephone'    => $validated['school_telephone'],
            'school_email'        => $validated['school_email'],
            'footer_ig'           => $validated['footer_ig'] ?? $config->footer_ig,
            'footer_yt'           => $validated['footer_yt'] ?? $config->footer_yt,
            'footer_fb'           => $validated['footer_fb'] ?? $config->footer_fb,
            'footer_linkedin'     => $validated['footer_linkedin'] ?? $config->footer_linkedin,
            'updated_by'          => Auth::id(),
        ]);

        $config->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi global berhasil diperbarui.',
            'data'    => $this->formatDetail($config),
        ]);
    }

    // Helper format response
    private function formatDetail(GlobalConfig $config): array
    {
        return [
            'id'                  => $config->id,
            'profile_title'       => $config->profile_title,
            'profile_description' => $config->profile_description,
            'img_profile_1'       => $config->img_profile_1,
            'img_profile_2'       => $config->img_profile_2,
            'school_vision'       => $config->school_vision,
            'video_profile'       => $config->video_profile,
            'school_name'         => $config->school_name,
            'footer_description'  => $config->footer_description,
            'motto'               => $config->motto,
            'school_telephone'    => $config->school_telephone,
            'school_email'        => $config->school_email,
            'footer_ig'           => $config->footer_ig,
            'footer_yt'           => $config->footer_yt,
            'footer_fb'           => $config->footer_fb,
            'footer_linkedin'     => $config->footer_linkedin,
            'created_by_fullname' => $config->createdBy?->fullname,
            'created_at'          => $config->created_at?->format('Y-m-d H:i:s'),
            'updated_by_fullname' => $config->updatedBy?->fullname,
            'updated_at'          => $config->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}