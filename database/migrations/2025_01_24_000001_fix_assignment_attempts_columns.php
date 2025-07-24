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
        Schema::table('assignment_attempts', function (Blueprint $table) {
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('assignment_attempts', 'status')) {
                $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress')->after('answers');
            }
            
            // Add student_id as an alias to user_id for backward compatibility
            if (!Schema::hasColumn('assignment_attempts', 'student_id')) {
                $table->unsignedBigInteger('student_id')->nullable()->after('user_id');
                $table->index('student_id');
            }
        });
        
        // Update existing records to set student_id = user_id and status based on completed_at
        DB::statement('UPDATE assignment_attempts SET student_id = user_id WHERE student_id IS NULL');
        DB::statement('UPDATE assignment_attempts SET status = "completed" WHERE completed_at IS NOT NULL AND status = "in_progress"');
        
        // Make student_id not nullable after updating existing records
        Schema::table('assignment_attempts', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignment_attempts', function (Blueprint $table) {
            $table->dropColumn(['status', 'student_id']);
        });
    }
};