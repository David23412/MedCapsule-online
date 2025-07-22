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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the course
            $table->text('description')->nullable(); // Description of the course (optional)
            $table->string('academic_year')->default('2024-1'); // Academic year and semester (e.g., 2024-1)
            $table->string('image')->nullable(); // Image URL for the course
            $table->string('color')->nullable(); // Color for the course
            $table->string('title_color')->default('#FFFFFF'); // Title color for the course
            $table->float('progress')->default(0); // Progress in the course based on mastered topics
            $table->string('image_path')->nullable();
            $table->decimal('price', 10, 2)->nullable(); // Course price (null for free courses)
            $table->string('currency')->default('EGP'); // Currency for the price
            $table->unsignedInteger('cache_version')->default(1); // Cache version for invalidation
            $table->timestamps();
            
            // Indexes for performance
            $table->index('academic_year', 'idx_academic_year');
            $table->index(['academic_year', 'price'], 'idx_academic_year_price');
            $table->index('created_at', 'idx_courses_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
