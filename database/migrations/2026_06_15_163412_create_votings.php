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
        Schema::create('votings', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->text('img_cover')->nullable();
            $table->string('title');
            $table->string('description');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('votings');
    }
};
