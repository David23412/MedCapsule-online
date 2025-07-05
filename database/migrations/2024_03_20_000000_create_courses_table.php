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
            $table->string('image')->nullable(); // Image URL for the course
            $table->string('color')->nullable(); // Color for the course
            $table->string('title_color')->default('#FFFFFF'); // Title color for the course
            $table->float('progress')->default(0); // Progress in the course based on mastered topics
            $table->string('image_path')->nullable();
            $table->timestamps();
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
