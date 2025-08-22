<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // CRITICAL: Force MySQL to use UTC timezone to prevent assignment visibility issues
        if (DB::getDriverName() === 'mysql') {
            DB::statement("SET time_zone = '+00:00'");
            DB::statement("SET SESSION time_zone = '+00:00'");
        }
        
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            // 🔥 RUTHLESS REMOVAL: No tutor_id needed - auth()->user() knows who's creating!
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->datetime('deadline');
            $table->decimal('duration_minutes', 8, 2)->default(60.00);
            $table->string('assignment_mode')->default('complete_topic');
            $table->string('assigned_source')->nullable();
            $table->timestamps();
            
            // 🚀 LEAN INDEXES: Only essential ones (detailed indexes in nuclear_performance_indexing)
            $table->index('deadline');
            $table->index('course_id');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ✅ FIXED: Remove foreign key constraint disabling - maintain data integrity
        if (Schema::hasTable('assignment_user')) {
            Schema::drop('assignment_user');
        }
        Schema::dropIfExists('assignments');
    }
};