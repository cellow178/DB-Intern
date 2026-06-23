<?php

namespace App\Services;

use App\Models\GlobalConfig;
use App\Models\Mission;
use Illuminate\Support\Facades\DB;

class VisionMissionService
{
    /**
     * Bisnis Logika 1: Mengambil Visi dan semua Misi yang aktif secara berurutan.
     */
    public function getVisionAndMissions(): array
    {
        // Mengambil data visi sekolah dari baris pertama tabel global_config
        $config = GlobalConfig::select('school_vission')->first();
        
        // Mengambil seluruh misi aktif yang diurutkan dari terkecil ke terbesar (1, 2, 3...)
        $missionsData = Mission::where('status_code', true)
            ->with('creator:id,username') 
            ->orderBy('order', 'asc')
            ->get(['id', 'content', 'order', 'created_by']);
        
        $missions = $missionsData->map(function ($mission) {
            return [
                'id' => $mission->id,
                'content' => $mission->content,
                'order' => $mission->order,
                'created_by' => $mission->creator ? $mission->creator->username : null,
            ];
        });

        return [
            'vision' => $config ? $config->school_vission : null,
            'missions' => $missions
        ];
    }

    /**
     * Bisnis Logika 2: Sinkronisasi Visi dan Misi (Tambah, Perbarui, Hapus, & Atur Urutan)
     */
    public function updateVisionAndMissions(string $visionContent, array $missionsData, int $userId): bool
    {
        // Membungkus proses dengan Transaksi Database
        return DB::transaction(function () use ($visionContent, $missionsData, $userId) {
            
            // 1. Ambil atau buat data global config pertama jika belum ada baris sama sekali
            $config = GlobalConfig::first();
            if ($config) {
                $config->update([
                    'school_vission' => $visionContent,
                    'updated_by' => $userId
                ]);
            } else {
                // Fallback jika database benar-benar kosong saat pertama kali setup
                GlobalConfig::create([
                    'school_vission' => $visionContent,
                    'profile_title' => 'Default Title',
                    'profile_description' => 'Default Description',
                    'img_profile_1' => '-',
                    'img_profile_2' => '-',
                    'video_profile' => '-',
                    'school-name' => 'Default School',
                    'footer_description' => '-',
                    'motto' => '-',
                    'school_telephone' => '-',
                    'school_email' => 'admin@school.com',
                    'created_by' => $userId
                ]);
            }

            // 2. Proses sinkronisasi urutan Misi
            $currentMissionIds = [];

            foreach ($missionsData as $index => $missionItem) {
                // Urutan otomatis diatur berdasarkan posisi urutan indeks data array yang dikirim frontend (mulai dari 1)
                $autoOrder = $index + 1;

                if (!empty($missionItem['id'])) {
                    // Skenario A: Jika memiliki ID, berarti data misi lama yang sedang diperbarui text / urutannya
                    $mission = Mission::find($missionItem['id']);
                    if ($mission) {
                        $mission->update([
                            'content' => $missionItem['content'],
                            'order' => $autoOrder,
                            'status_code' => $missionItem['status_code'] ?? true,
                            'updated_by' => $userId
                        ]);
                        $currentMissionIds[] = $mission->id;
                    }
                } else {
                    // Skenario B: Jika ID kosong/null, berarti ini adalah misi baru yang ditambahkan user
                    $newMission = Mission::create([
                        'content' => $missionItem['content'],
                        'order' => $autoOrder,
                        'status_code' => true,
                        'created_by' => $userId
                    ]);
                    $currentMissionIds[] = $newMission->id;
                }
            }

            // 3. Bersihkan Misi Lama: Hapus misi dari database jika tidak dikirimkan lagi di array terbaru
            Mission::whereNotIn('id', $currentMissionIds)->delete();

            return true;
        });
    }

    /**
     * Bisnis Logika 3: Membuat satu data misi baru secara mandiri (satuan)
     */
    public function storeMission(array $data, int $userId): Mission
    {
        // Mencari nilai urutan ('order') paling tinggi saat ini di database
        $maxOrder = Mission::max('order') ?? 0;

        // Simpan data misi baru ke database
        return Mission::create([
            'content'     => $data['content'],
            'order'       => $maxOrder + 1, // Otomatis taruh di urutan paling bawah
            'status_code' => $data['status_code'] ?? true,
            'created_by'  => $userId,
        ]);
    }
}