<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('global_config', function (Blueprint $table) {
            $table->id();
            $table->string('hero_description', 100);
            $table->string('profile_title');
            $table->text('profile_description');
            $table->text('img_profile_1');
            $table->text('img_profile_2')->nullable();
            $table->text('school_vision');
            $table->text('video_profile')->nullable();
            $table->string('school_name', 150);
            $table->string('footer_description')->nullable();
            $table->string('motto', 100);
            $table->string('school_telephone', 150);
            $table->string('school_email');
            $table->text('footer_ig')->nullable();
            $table->text('footer_yt')->nullable();
            $table->text('footer_fb')->nullable();
            $table->text('footer_linkedin')->nullable();
            $table->text('map_embed')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestampsTz($precision = 0);
        });

        $data = [
            [
                'hero_description' => 'Berprestasi, berkarakter, dan siap menjadi generasi hebat Indonesia.',
                'profile_title' => 'Profil Sekolah',
                'profile_description' => '<p><strong style="color: rgb(0, 0, 0);">SMKN 7 Semarang</strong><span style="color: rgb(0, 0, 0);"> diresmikan pada tanggal 7 Juni 1971 oleh Presiden Republik Indonesia - Soeharto, dengan nama Proyek Perintis Sekolah Teknologi Menengah Pembangunan Semarang dengan lama pendidikan 4 (empat) tahun.</span></p><p><br></p><p><span style="color: rgb(0, 0, 0);">Dikenal dengan sebutan </span><strong style="color: rgb(0, 0, 0);">STEMBA</strong><span style="color: rgb(0, 0, 0);">, sekolah ini unggul dalam mencetak lulusan dengan keahlian praktis, sering kali memiliki ikatan kerja (penyaluran kerja) yang tinggi di industri.</span></p>',
                'img_profile_1' => 'default_profile1.jpg',
                'img_profile_2' => 'default_profile2.jpg',
                'school_vision' => 'Menjadi Sekolah Internasional Tahun 2030.',
                'video_profile' => 'https://www.youtube.com/watch?v=9VNvxo9Ze2Q&t=1s',
                'school_name' => 'SMKN 7 Semarang',
                'footer_description' => 'SMK Negeri 7 Semarang merupakan salah satu SMK unggulan di Jawa Tengah yang berfokus menciptakan lulusan berkualitas, berkarakter, dan siap kerja.',
                'motto' => 'Tiada Hari Tanpa Prestasi',
                'school_telephone' => '(024) 8311532',
                'school_email' => 'admin@smkn7semarang.sch.id',
                'footer_ig' => '',
                'footer_yt' => '',
                'footer_fb' => '',
                'footer_linkedin' => '',
                'map_embed' => '',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        DB::table('global_config')->insert($data);

        DB::statement("SELECT setval('global_config_id_seq', (SELECT MAX(id) FROM global_config)+1)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_config');
    }
};
