<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 🔥 ESSENTIAL PERFORMANCE INDEXES 🔥
     * 
     * Adds only the most critical performance indexes that are missing
     */
    public function up(): void
    {
        // Add essential indexes only if they don't exist
        $this->addIndexSafely('users', 'role', 'idx_users_role');
        $this->addIndexSafely('users', 'xp', 'idx_users_xp');
        $this->addIndexSafely('users', 'study_streak_days', 'idx_users_streak');
        
        $this->addIndexSafely('courses', 'type', 'idx_courses_type');
        $this->addIndexSafely('courses', 'academic_year', 'idx_courses_year');
        $this->addIndexSafely('courses', 'is_active', 'idx_courses_active');
        
        $this->addIndexSafely('topics', 'is_locked', 'idx_topics_locked');
        $this->addIndexSafely('topics', 'case_type', 'idx_topics_case_type');
        
        $this->addIndexSafely('questions', 'question_type', 'idx_questions_type');
        $this->addIndexSafely('questions', 'source', 'idx_questions_source');
        
        $this->addIndexSafely('quiz_attempts', 'mode', 'idx_attempts_mode');
        $this->addIndexSafely('quiz_attempts', 'assignment_id', 'idx_attempts_assignment');
        $this->addIndexSafely('quiz_attempts', 'source', 'idx_attempts_source');
        
        $this->addIndexSafely('assignments', 'assignment_mode', 'idx_assignments_mode');
        $this->addIndexSafely('assignments', 'assigned_source', 'idx_assignments_source');
        
        $this->addIndexSafely('payments', 'status', 'idx_payments_status');
        $this->addIndexSafely('payments', 'payment_method', 'idx_payments_method');
        
        echo "✅ Essential performance indexes added successfully!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'users' => ['idx_users_role', 'idx_users_xp', 'idx_users_streak'],
            'courses' => ['idx_courses_type', 'idx_courses_year', 'idx_courses_active'],
            'topics' => ['idx_topics_locked', 'idx_topics_case_type'],
            'questions' => ['idx_questions_type', 'idx_questions_source'],
            'quiz_attempts' => ['idx_attempts_mode', 'idx_attempts_assignment', 'idx_attempts_source'],
            'assignments' => ['idx_assignments_mode', 'idx_assignments_source'],
            'payments' => ['idx_payments_status', 'idx_payments_method']
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $tableSchema) use ($tableIndexes) {
                    foreach ($tableIndexes as $index) {
                        try {
                            $tableSchema->dropIndex($index);
                        } catch (\Exception $e) {
                            // Index might not exist, continue
                        }
                    }
                });
            }
        }
    }

    /**
     * Safely add an index only if it doesn't exist
     */
    private function addIndexSafely(string $table, string $column, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            echo "⚠️  Table {$table} does not exist, skipping index {$indexName}\n";
            return;
        }

        if (!Schema::hasColumn($table, $column)) {
            echo "⚠️  Column {$column} does not exist in {$table}, skipping index {$indexName}\n";
            return;
        }

        if ($this->indexExists($table, $indexName)) {
            echo "ℹ️  Index {$indexName} already exists on {$table}\n";
            return;
        }

        try {
            Schema::table($table, function (Blueprint $tableSchema) use ($column, $indexName) {
                $tableSchema->index($column, $indexName);
            });
            echo "✅ Added index {$indexName} to {$table}.{$column}\n";
        } catch (\Exception $e) {
            echo "❌ Failed to add index {$indexName} to {$table}: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}`");
            foreach ($indexes as $index) {
                if ($index->Key_name === $indexName) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
};