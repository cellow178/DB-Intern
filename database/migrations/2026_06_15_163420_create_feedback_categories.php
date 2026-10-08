<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feedbacks_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name', 100)->unique();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestampsTz($precision = 0);
        });

        $data = [
            [
                "id" => 1,
                "category_name" => "Lainnya",
                "active" => true,
                "created_by" => 1,
                "updated_by" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => 2,
                "category_name" => "Fasilitas",
                "active" => true,
                "created_by" => 1,
                "updated_by" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => 3,
                "category_name" => "Event",
                "active" => true,
                "created_by" => 1,
                "updated_by" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => 4,
                "category_name" => "Akademik",
                "active" => true,
                "created_by" => 1,
                "updated_by" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => 5,
                "category_name" => "Kebersihan",
                "active" => true,
                "created_by" => 1,
                "updated_by" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => 6,
                "category_name" => "Keamanan",
                "active" => true,
                "created_by" => 1,
                "updated_by" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ];
        DB::table('feedbacks_categories')->insert($data);
        DB::statement("SELECT setval('feedbacks_categories_id_seq', (SELECT MAX(id) FROM feedbacks_categories)+1)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks_categories');
    }
};
