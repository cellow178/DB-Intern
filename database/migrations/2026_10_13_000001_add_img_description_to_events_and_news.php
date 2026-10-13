<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('img_description')->nullable()->after('img_cover');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->string('img_description')->nullable()->after('img_cover');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('img_description');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('img_description');
        });
    }
};
