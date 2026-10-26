<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 1 voting: 2 mode (tps / remote), DPT freeze, suara anonim.
     *
     * - votings: kolom mode + konfigurasi DPT + snapshot dpt_total.
     * - voting_dpt: snapshot pemilih saat voting dibuka (user_id TANPA FK
     *   agar purge lulusan tidak merusak DPT/hasil; identitas disnapshot).
     * - voting_receipts: siapa sudah memilih (tanpa pilihan) — cegah ganda.
     * - voting_ballots: pilihan apa (tanpa nama) — anonim penuh.
     * - voting_passes: pass bilik TPS (10 mnt) + token tersegel remote
     *   (s.d. end_date) + ballot token sekali pakai. Hanya hash disimpan.
     * - permissions votings untuk role developer/super-admin/guru.
     */
    public function up(): void
    {
        Schema::table('votings', function (Blueprint $table) {
            $table->string('mode', 10)->default('tps'); // tps | remote
            $table->boolean('include_siswa')->default(true);
            $table->boolean('include_teachers')->default(false);
            $table->json('masa_studi_list')->nullable(); // cth: [3,4]
            $table->json('tahun_masuk_list')->nullable(); // cth: [2023,2024]
            $table->boolean('include_tingkat_13')->default(true); // false = kelas 13 tidak ikut
            $table->integer('dpt_total')->default(0);
            $table->timestampTz('dpt_frozen_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
        });

        Schema::create('voting_dpt', function (Blueprint $table) {
            $table->id();
            // Snapshot: tanpa FK ke users agar purge alumni tidak menghapus DPT.
            $table->foreignId('voting_id')->constrained('votings')->onDelete('cascade');
            $table->bigInteger('user_id')->nullable();
            $table->string('fullname', 255);
            $table->string('username', 100);
            $table->bigInteger('role_id')->nullable();
            $table->integer('tahun_masuk')->nullable();
            $table->integer('masa_studi')->nullable();
            $table->timestampTz('voted_at')->nullable();
            $table->timestampsTz(0);
            $table->unique(['voting_id', 'user_id']);
            $table->index('voting_id');
        });

        Schema::create('voting_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_id')->constrained('votings')->onDelete('cascade');
            $table->bigInteger('user_id');
            $table->timestampTz('voted_at')->nullable();
            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampsTz(0);
            // Satu pemilih satu suara — ditegakkan database, bukan frontend.
            $table->unique(['voting_id', 'user_id']);
        });

        Schema::create('voting_ballots', function (Blueprint $table) {
            $table->id();
            // Anonim: hanya pilihan, tanpa user_id. Urutan insert diacak
            // secara alami oleh konkurensi TPS; tidak ada kolom waktu presisi
            // yang diekspos ke publik untuk korelasi.
            $table->foreignId('voting_id')->constrained('votings')->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('voting_candidates')->onDelete('cascade');
            $table->timestampsTz(0);
            $table->index(['voting_id', 'candidate_id']);
        });

        Schema::create('voting_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_id')->constrained('votings')->onDelete('cascade');
            $table->bigInteger('user_id');
            // Pass bilik (kolom pass_*) + ballot token (kolom ballot_*).
            $table->string('pass_hash', 64)->nullable();
            $table->timestampTz('pass_expired_at')->nullable();
            $table->timestampTz('pass_used_at')->nullable();
            $table->string('ballot_token_hash', 64)->nullable();
            $table->timestampTz('ballot_expired_at')->nullable();
            $table->timestampTz('ballot_used_at')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->timestampsTz(0);
            $table->index(['voting_id', 'user_id']);
        });

        // Permissions modul voting (5 aksi standar) + mapping ke
        // developer (-1), super-admin (1), guru (2). Siswa (3) tidak diberi.
        $actions = [
            'view' => 'Lihat',
            'show' => 'Detail',
            'create' => 'Tambah',
            'update' => 'Edit',
            'delete' => 'Hapus',
        ];
        foreach ($actions as $action => $actionName) {
            $exists = DB::table('permissions')->where('permission_code', "{$action}-votings")->exists();
            if (!$exists) {
                $id = DB::table('permissions')->insertGetId([
                    'permission_code' => "{$action}-votings",
                    'permission_name' => "{$actionName} Voting",
                    'permission_group' => 'votings',
                    'description' => "{$actionName} Voting",
                    'active' => true,
                ]);
                foreach ([-1, 1, 2] as $roleId) {
                    DB::table('mapping_roles_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $id,
                        'active' => true,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $permIds = DB::table('permissions')
            ->whereIn('permission_code', [
                'view-votings', 'show-votings', 'create-votings', 'update-votings', 'delete-votings',
            ])->pluck('id');
        if ($permIds->isNotEmpty()) {
            DB::table('mapping_roles_permissions')->whereIn('permission_id', $permIds)->delete();
            DB::table('permissions')->whereIn('id', $permIds)->delete();
        }

        Schema::dropIfExists('voting_passes');
        Schema::dropIfExists('voting_ballots');
        Schema::dropIfExists('voting_receipts');
        Schema::dropIfExists('voting_dpt');

        Schema::table('votings', function (Blueprint $table) {
            $table->dropColumn([
                'mode', 'include_siswa', 'include_teachers', 'masa_studi_list',
                'tahun_masuk_list', 'include_tingkat_13', 'dpt_total',
                'dpt_frozen_at', 'closed_at',
            ]);
        });
    }
};
