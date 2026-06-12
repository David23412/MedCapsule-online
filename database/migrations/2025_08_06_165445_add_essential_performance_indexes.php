<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexSafely('users', 'role', 'idx_users_role');
        $this->addIndexSafely('users', 'xp', 'idx_users_xp');
        $this->addIndexSafely('users', 'study_streak_days', 'idx_users_streak');
        $this->addIndexSafely('users', 'email', 'idx_users_email');
        $this->addIndexSafely('users', 'last_active_at', 'idx_users_last_active');
        $this->addIndexSafely('users', 'created_at', 'idx_users_created_at');

        // ⚡ QUIZ_ATTEMPTS TABLE - HIGH FREQUENCY OPERATIONS
        $this->addIndexSafely('quiz_attempts', 'assignment_id', 'idx_attempts_assignment');
        $this->addIndexSafely('quiz_attempts', 'source', 'idx_attempts_source');
        $this->addIndexSafely('quiz_attempts', 'quiz_type', 'idx_attempts_quiz_type');
        $this->addIndexSafely('quiz_attempts', 'score', 'idx_attempts_score');
        $this->addIndexSafely('quiz_attempts', 'percentage_grade', 'idx_attempts_percentage');
        $this->addIndexSafely('quiz_attempts', 'duration_seconds', 'idx_attempts_duration');

        // ⚡ OTHER FREQUENT TABLES
        $this->addIndexSafely('courses', 'type', 'idx_courses_type');
        $this->addIndexSafely('courses', 'academic_year', 'idx_courses_year');
        $this->addIndexSafely('courses', 'price', 'idx_courses_price');

        $this->addIndexSafely('topics', 'is_locked', 'idx_topics_locked');
        $this->addIndexSafely('topics', 'case_type', 'idx_topics_case_type');
        $this->addIndexSafely('topics', 'display_order', 'idx_topics_display_order');

        $this->addIndexSafely('questions', 'question_type', 'idx_questions_type');
        $this->addIndexSafely('questions', 'source', 'idx_questions_source');

        $this->addIndexSafely('assignments', 'assignment_mode', 'idx_assignments_mode');
        $this->addIndexSafely('assignments', 'assigned_source', 'idx_assignments_source');
        $this->addIndexSafely('assignments', 'deadline', 'idx_assignments_deadline');

        $this->addIndexSafely('mistakes', 'mastered', 'idx_mistakes_mastered');
        $this->addIndexSafely('mistakes', 'skipped', 'idx_mistakes_skipped');

        $this->addCompositeIndexSafely('users', ['role', 'last_active_at'], 'idx_user_role_active');
        $this->addCompositeIndexSafely('users', ['role', 'xp'], 'idx_user_role_xp');
        $this->addCompositeIndexSafely('users', ['study_streak_days', 'last_active_at'], 'idx_user_streak_active');
        $this->addCompositeIndexSafely('users', ['xp', 'created_at'], 'idx_user_xp_created');

        $this->addCompositeIndexSafely('quiz_attempts', ['user_id', 'created_at'], 'idx_quiz_attempts_user_date');
        $this->addCompositeIndexSafely('quiz_attempts', ['user_id', 'topic_id', 'created_at'], 'idx_quiz_attempts_composite');
        $this->addCompositeIndexSafely('quiz_attempts', ['assignment_id', 'user_id', 'created_at'], 'idx_quiz_attempts_assignment');
        $this->addCompositeIndexSafely('quiz_attempts', ['user_id', 'quiz_type', 'created_at'], 'idx_quiz_attempts_type');
        $this->addCompositeIndexSafely('quiz_attempts', ['user_id', 'score'], 'idx_quiz_attempts_user_score');
        $this->addCompositeIndexSafely('quiz_attempts', ['user_id', 'percentage_grade'], 'idx_quiz_attempts_user_grade');
        $this->addCompositeIndexSafely('quiz_attempts', ['created_at', 'score'], 'idx_quiz_attempts_date_score');

        $this->addCompositeIndexSafely('course_user', ['user_id', 'course_id'], 'idx_course_user_enrolled');
        $this->addCompositeIndexSafely('course_user', ['course_id', 'created_at'], 'idx_course_enrollments');
        $this->addCompositeIndexSafely('course_user', ['user_id', 'created_at'], 'idx_course_user_date');

        $this->addCompositeIndexSafely('topics', ['course_id', 'is_locked'], 'idx_topics_course_locked');
        $this->addCompositeIndexSafely('topics', ['course_id', 'display_order'], 'idx_topics_display_order');
        $this->addCompositeIndexSafely('topics', ['course_id', 'case_type'], 'idx_topics_course_type');

        $this->addCompositeIndexSafely('user_topic_progress', ['user_id', 'topic_id', 'updated_at'], 'idx_user_topic_progress');
        $this->addCompositeIndexSafely('user_topic_progress', ['user_id', 'percentage_grade'], 'idx_user_topic_progress_grade');
        $this->addCompositeIndexSafely('user_topic_progress', ['topic_id', 'percentage_grade'], 'idx_topic_progress_grade');

        $this->addCompositeIndexSafely('assignments', ['course_id', 'deadline'], 'idx_assignments_course_deadline');
        $this->addCompositeIndexSafely('assignments', ['course_id', 'assignment_mode'], 'idx_assignments_mode');

        $this->addCompositeIndexSafely('mistakes', ['user_id', 'mastered'], 'idx_mistakes_user_mastered');
        $this->addCompositeIndexSafely('mistakes', ['question_id', 'mastered'], 'idx_mistakes_question_mastered');
        $this->addCompositeIndexSafely('mistakes', ['user_id', 'created_at'], 'idx_mistakes_user_date');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: indexes are harmless to keep on rollback and may be required by FKs
        return;
    }

    /**
     * Safely add an index only if it doesn't exist
     */
    private function addIndexSafely(string $table, string $column, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->hasEquivalentIndex($table, [$column])) {
            return;
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $tableSchema) use ($column, $indexName) {
                $tableSchema->index($column, $indexName);
            });
        } catch (\Throwable $e) {
            Log::warning('Failed to add migration index', [
                'table' => $table,
                'column' => $column,
                'index' => $indexName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Safely add a composite index only if it doesn't exist
     */
    private function addCompositeIndexSafely(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        // Check if all columns exist
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->hasEquivalentIndex($table, $columns)) {
            return;
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $tableSchema) use ($columns, $indexName) {
                $tableSchema->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            Log::warning('Failed to add migration composite index', [
                'table' => $table,
                'columns' => $columns,
                'index' => $indexName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        return array_key_exists($indexName, $this->getIndexDefinitions($table));
    }

    /**
     * Check whether an equivalent index definition already exists, regardless of name.
     */
    private function hasEquivalentIndex(string $table, array $columns): bool
    {
        foreach ($this->getIndexDefinitions($table) as $definition) {
            if (array_slice($definition, 0, count($columns)) === $columns) {
                return true;
            }
        }

        return false;
    }

    private function getIndexDefinitions(string $table): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        try {
            return match ($driver) {
                'sqlite' => $this->sqliteIndexDefinitions($table),
                'pgsql' => $this->pgsqlIndexDefinitions($table),
                default => $this->mysqlIndexDefinitions($table),
            };
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function mysqlIndexDefinitions(string $table): array
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}`");
        $definitions = [];

        foreach ($indexes as $index) {
            $keyName = $index->Key_name ?? null;
            $seq = (int) ($index->Seq_in_index ?? 0);
            $column = $index->Column_name ?? null;

            if ($keyName === null || $seq < 1 || $column === null) {
                continue;
            }

            $definitions[$keyName][$seq] = $column;
        }

        foreach ($definitions as &$definition) {
            ksort($definition);
            $definition = array_values($definition);
        }

        return $definitions;
    }

    private function sqliteIndexDefinitions(string $table): array
    {
        $tableLiteral = str_replace("'", "''", $table);
        $indexes = DB::select("PRAGMA index_list('{$tableLiteral}')");
        $definitions = [];

        foreach ($indexes as $index) {
            $indexName = $index->name ?? null;

            if ($indexName === null) {
                continue;
            }

            $indexLiteral = str_replace("'", "''", $indexName);
            $columns = DB::select("PRAGMA index_info('{$indexLiteral}')");

            foreach ($columns as $column) {
                $seq = (int) (($column->seqno ?? 0) + 1);
                $columnName = $column->name ?? null;

                if ($columnName === null) {
                    continue;
                }

                $definitions[$indexName][$seq] = $columnName;
            }
        }

        foreach ($definitions as &$definition) {
            ksort($definition);
            $definition = array_values($definition);
        }

        return $definitions;
    }

    private function pgsqlIndexDefinitions(string $table): array
    {
        $indexes = DB::select(
            "SELECT indexname, indexdef FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ?",
            [$table]
        );

        $definitions = [];

        foreach ($indexes as $index) {
            $indexName = $index->indexname ?? null;
            $indexDef = $index->indexdef ?? null;

            if ($indexName === null || !is_string($indexDef)) {
                continue;
            }

            if (!preg_match('/\((.+)\)/', $indexDef, $matches)) {
                continue;
            }

            $columns = array_map(
                static fn (string $column): string => trim(str_replace('"', '', $column)),
                explode(',', $matches[1])
            );

            $definitions[$indexName] = array_values(array_filter($columns));
        }

        return $definitions;
    }
};
