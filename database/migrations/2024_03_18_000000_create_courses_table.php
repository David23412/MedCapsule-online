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
            $table->enum('type', ['medical', 'custom'])->default('medical'); // Course type
            $table->string('academic_year')->default('2024-1'); // Academic year and semester (e.g., 2024-1)
            $table->string('image')->nullable(); // Image URL for the course
            $table->string('color')->nullable(); // Color for the course
            $table->string('title_color')->default('#FFFFFF'); // Title color for the course
            $table->decimal('price', 10, 2)->nullable(); // Course price (null for free courses)
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
