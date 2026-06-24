<?php

// app/Http/Controllers/Admin/VisionMissionController.php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\VisionMissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class VisionMissionController extends Controller
{
    protected \App\Services\VisionMissionService $visionMissionService;

    // Dependency Injection: Memasukkan logika bisnis service ke dalam controller
    public function __construct(VisionMissionService $visionMissionService)
    {
        $this->visionMissionService = $visionMissionService;
    }

    public function index(): JsonResponse
    {
        $data = $this->visionMissionService->getVisionAndMissions();
        
        return response()->json([
            'success' => true,
            'message' => 'Data Visi & Misi berhasil dimuat.',
            'data' => $data
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'school_vission' => 'required|string',
            'missions' => 'required|array',
            'missions.*.id' => 'nullable|integer|exists:missions,id',
            'missions.*.content' => 'required|string',
        ]);

        // Menggunakan User ID = 1 (Developer) dari migration jika belum ada session login
        $userId = Auth::id() ?? 1;
        $this->visionMissionService->updateVisionAndMissions(
            $request->input('school_vission'),
            $request->input('missions'),
            $userId
        );

        return response()->json([
            'success' => true,
            'message' => 'Visi dan Misi sekolah berhasil diperbarui.'
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string',
            'status_code' => 'nullable|boolean',
        ]);

        $userId = Auth::id() ?? 1;
        
        $newMission = $this->visionMissionService->storeMission(
            $request->only(['content', 'status_code']),
            $userId
        );

        return response()->json([
            'success' => true,
            'message' => 'Misi baru berhasil ditambahkan.',
            'data' => $newMission
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->visionMissionService->destroyMission($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Data misi tidak ditemukan atau gagal dihapus.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Misi berhasil dihapus.'
        ]);
    }
}