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
            $table->enum('status', ['active', 'overdue', 'locked', 'expired'])->default('active');
            $table->timestamps();
            
            // 🚀 LEAN INDEXES: Only essential ones (detailed indexes in nuclear_performance_indexing)
            $table->index('deadline');
            $table->index('course_id');
        });

        // Create a trigger that automatically updates assignment status (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('
                CREATE TRIGGER update_assignment_status_on_select
                BEFORE UPDATE ON assignments
                FOR EACH ROW
                BEGIN
                    -- Auto-update status based on deadline
                    IF NEW.deadline < NOW() AND NEW.status = "active" THEN
                        SET NEW.status = "overdue";
                    END IF;
                    
                    -- Auto-reactivate if deadline is extended
                    IF NEW.deadline > NOW() AND NEW.status = "overdue" THEN
                        SET NEW.status = "active";
                    END IF;
                END
            ');
        }
        
        // Create a stored procedure to update overdue assignments (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('
                DROP PROCEDURE IF EXISTS UpdateOverdueAssignments;
                CREATE PROCEDURE UpdateOverdueAssignments()
                BEGIN
                    UPDATE assignments 
                    SET status = "overdue" 
                    WHERE status = "active" 
                    AND deadline < NOW();
                END
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS update_assignment_status_on_select');
            DB::unprepared('DROP PROCEDURE IF EXISTS UpdateOverdueAssignments');
        }
        Schema::dropIfExists('assignments');
    }
};