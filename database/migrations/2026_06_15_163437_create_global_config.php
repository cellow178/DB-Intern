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
                'hero_description' => 'Selamat Datang di Website Resmi Sekolah Kami',
                'profile_title' => 'Profil Singkat Sekolah',
                'profile_description' => 'Deskripsi lengkap mengenai profil sekolah, sejarah, dan berbagai keunggulan yang dimiliki.',
                'img_profile_1' => 'default_profile1.jpg',
                'img_profile_2' => 'default_profile2.jpg',
                'school_vision' => 'Mewujudkan lulusan yang unggul, berkarakter, dan berwawasan global.',
                'video_profile' => 'https://www.youtube.com/embed/example',
                'school_name' => 'SMK Negeri Contoh',
                'footer_description' => 'Deskripsi singkat pada bagian footer website.',
                'motto' => 'Unggul dalam Prestasi, Santun dalam Perilaku',
                'school_telephone' => '+62 274 123456',
                'school_email' => 'info@sekolahcontoh.sch.id',
                'footer_ig' => 'https://instagram.com/sekolah',
                'footer_yt' => 'https://youtube.com/sekolah',
                'footer_fb' => 'https://facebook.com/sekolah',
                'footer_linkedin' => 'https://linkedin.com/school/sekolah',
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
