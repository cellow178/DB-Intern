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
        Schema::create('global_config', function (Blueprint $table) {
            $table->id();
            $table->string('profile_title');
            $table->text('profile_description');
            $table->text('img_profile_1');
            $table->text('img_profile_2');
            $table->text('school_vission');
            $table->text('video_profile');
            $table->string('school-name', 150);
            $table->string('footer_description');
            $table->string('motto', 100);
            $table->string('school_telephone', 150);
            $table->string('school_email');
            $table->text('footer_ig')->nullable();
            $table->text('footer_yt')->nullable();
            $table->text('footer_fb')->nullable();
            $table->text('footer_linkedin')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestampsTz($precision = 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_config');
    }
};
