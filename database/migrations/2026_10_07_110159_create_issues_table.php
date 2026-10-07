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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('issue_number')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('cover_image');
            $table->string('pdf_file_path');
            $table->date('published_at');
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft');
            $table->unsignedInteger('pages_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
