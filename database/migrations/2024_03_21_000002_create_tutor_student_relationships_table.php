<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::create('tutor_student_relationships', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys with proper indexing
            $table->foreignId('tutor_id')
                  ->constrained('users')
                  ->onDelete('cascade');
                  
            $table->foreignId('student_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Add metadata columns
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            // Add unique constraint to prevent duplicate assignments
            $table->unique(['tutor_id', 'student_id'], 'unique_tutor_student');

            // Add composite index for faster lookups
            $table->index(['tutor_id', 'student_id', 'created_at']);
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutor_student_relationships');
    }
}; 