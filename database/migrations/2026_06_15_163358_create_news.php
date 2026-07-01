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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('news_categories');            
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('content');
            $table->text('img_cover')->nullable();
            $table->enum('status', ['archive', 'publish', 'draft']);
            $table->boolean('is_highlight')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestampsTz($precision = 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
