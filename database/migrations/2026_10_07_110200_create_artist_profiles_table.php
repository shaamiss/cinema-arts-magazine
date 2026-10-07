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
        Schema::create('artist_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('slug')->unique();
            $table->enum('art_category', ['theatre', 'cinema', 'fine_arts', 'music']);
            $table->text('biography');
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
            $table->string('avatar_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index('art_category');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_profiles');
    }
};
