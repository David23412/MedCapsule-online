<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensurePlannerCourseUserColumns();
        $this->ensureUserMistakeMasteryTable();
        $this->ensureQuizAttemptSessionType();

        $this->dropObsoleteView('assignment_dashboard_view');
        $this->dropObsoleteView('student_progress_view');

        $this->dropRedundantIndex('users', 'idx_users_role', ['role']);
        $this->dropRedundantIndex('users', 'idx_users_xp', ['xp']);
        $this->dropRedundantIndex('users', 'idx_users_streak', ['study_streak_days']);
        $this->dropRedundantIndex('users', 'idx_users_email', ['email']);
        $this->dropRedundantIndex('users', 'idx_users_last_active', ['last_active_at']);
        $this->dropRedundantIndex('users', 'idx_users_created_at', ['created_at']);

        $this->dropRedundantIndex('assignments', 'idx_assignments_deadline', ['deadline']);
        $this->dropRedundantIndex('course_user', 'idx_course_user_enrolled', ['user_id', 'course_id']);
        $this->dropRedundantIndex('mistakes', 'idx_mistakes_skipped', ['skipped']);
        $this->dropRedundantIndex('quiz_attempts', 'quiz_attempts_created_at_index', ['created_at']);
        $this->dropRedundantIndex('quiz_attempts', 'quiz_attempts_user_id_topic_id_index', ['user_id', 'topic_id']);
        $this->dropRedundantIndex('questions', 'questions_topic_id_index', ['topic_id']);
        $this->dropRedundantIndex('topics', 'idx_topics_case_type', ['case_type']);
        $this->dropRedundantIndex('topics', 'idx_topics_display_order', ['display_order']);
        $this->dropRedundantIndex('topics', 'idx_topics_locked', ['is_locked']);
    }

    public function down(): void
    {
        $this->dropQuizAttemptSessionType();
        $this->dropUserMistakeMasteryTable();
        $this->dropPlannerCourseUserColumns();

        $this->restoreIndex('users', ['role'], 'idx_users_role');
        $this->restoreIndex('users', ['xp'], 'idx_users_xp');
        $this->restoreIndex('users', ['study_streak_days'], 'idx_users_streak');
        $this->restoreIndex('users', ['email'], 'idx_users_email');
        $this->restoreIndex('users', ['last_active_at'], 'idx_users_last_active');
        $this->restoreIndex('users', ['created_at'], 'idx_users_created_at');

        $this->restoreIndex('assignments', ['deadline'], 'idx_assignments_deadline');
        $this->restoreIndex('course_user', ['user_id', 'course_id'], 'idx_course_user_enrolled');
        $this->restoreIndex('mistakes', ['skipped'], 'idx_mistakes_skipped');
        $this->restoreIndex('quiz_attempts', ['created_at'], 'quiz_attempts_created_at_index');
        $this->restoreIndex('quiz_attempts', ['user_id', 'topic_id'], 'quiz_attempts_user_id_topic_id_index');
        $this->restoreIndex('questions', ['topic_id'], 'questions_topic_id_index');
        $this->restoreIndex('topics', ['case_type'], 'idx_topics_case_type');
        $this->restoreIndex('topics', ['display_order'], 'idx_topics_display_order');
        $this->restoreIndex('topics', ['is_locked'], 'idx_topics_locked');
    }

    private function dropObsoleteView(string $viewName): void
    {
        \Illuminate\Support\Facades\DB::statement("DROP VIEW IF EXISTS {$viewName}");
    }

    private function ensurePlannerCourseUserColumns(): void
    {
        if (!Schema::hasTable('course_user')) {
            return;
        }

        Schema::table('course_user', function (Blueprint $tableSchema) {
            if (!Schema::hasColumn('course_user', 'planner_repair_mode')) {
                $tableSchema->string('planner_repair_mode', 20)->nullable()->after('planner_preferred_source');
            }

            if (!Schema::hasColumn('course_user', 'planner_mistake_mastery_mode')) {
                $tableSchema->string('planner_mistake_mastery_mode', 20)->nullable()->after('planner_repair_mode');
            }
        });

        if (!$this->indexExists('course_user', 'idx_course_user_planner_repair_mode')
            && !$this->hasEquivalentIndexExcluding('course_user', ['course_id', 'planner_repair_mode'], '')
            && Schema::hasColumn('course_user', 'planner_repair_mode')) {
            Schema::table('course_user', function (Blueprint $tableSchema) {
                $tableSchema->index(['course_id', 'planner_repair_mode'], 'idx_course_user_planner_repair_mode');
            });
        }
    }

    private function ensureUserMistakeMasteryTable(): void
    {
        if (!Schema::hasTable('user_mistake_mastery')) {
            Schema::create('user_mistake_mastery', function (Blueprint $tableSchema) {
                $tableSchema->id();
                $tableSchema->foreignId('user_id')->constrained()->onDelete('cascade');
                $tableSchema->foreignId('question_id')->constrained()->onDelete('cascade');
                $tableSchema->foreignId('course_id')->constrained()->onDelete('cascade');
                $tableSchema->unsignedInteger('consecutive_correct')->default(0);
                $tableSchema->timestamp('graduated_at')->nullable();
                $tableSchema->timestamp('last_attempt_at')->nullable();
                $tableSchema->string('last_attempt_result', 16)->nullable();
                $tableSchema->unsignedInteger('total_attempts')->default(0);
                $tableSchema->timestamps();

                $tableSchema->unique(['user_id', 'question_id']);
                $tableSchema->index(['user_id', 'course_id', 'graduated_at'], 'idx_umm_user_course_graduated');
                $tableSchema->index(['user_id', 'last_attempt_at'], 'idx_umm_user_last_attempt');
                $tableSchema->index(['course_id', 'graduated_at'], 'idx_umm_course_graduated');
            });

            return;
        }

        if (!$this->indexExists('user_mistake_mastery', 'idx_umm_user_course_graduated')
            && !$this->hasEquivalentIndexExcluding('user_mistake_mastery', ['user_id', 'course_id', 'graduated_at'], '')) {
            Schema::table('user_mistake_mastery', function (Blueprint $tableSchema) {
                $tableSchema->index(['user_id', 'course_id', 'graduated_at'], 'idx_umm_user_course_graduated');
            });
        }

        if (!$this->indexExists('user_mistake_mastery', 'idx_umm_user_last_attempt')
            && !$this->hasEquivalentIndexExcluding('user_mistake_mastery', ['user_id', 'last_attempt_at'], '')) {
            Schema::table('user_mistake_mastery', function (Blueprint $tableSchema) {
                $tableSchema->index(['user_id', 'last_attempt_at'], 'idx_umm_user_last_attempt');
            });
        }

        if (!$this->indexExists('user_mistake_mastery', 'idx_umm_course_graduated')
            && !$this->hasEquivalentIndexExcluding('user_mistake_mastery', ['course_id', 'graduated_at'], '')) {
            Schema::table('user_mistake_mastery', function (Blueprint $tableSchema) {
                $tableSchema->index(['course_id', 'graduated_at'], 'idx_umm_course_graduated');
            });
        }
    }

    private function dropUserMistakeMasteryTable(): void
    {
        if (Schema::hasTable('user_mistake_mastery')) {
            Schema::drop('user_mistake_mastery');
        }
    }

    private function ensureQuizAttemptSessionType(): void
    {
        if (!Schema::hasTable('quiz_attempts') || Schema::hasColumn('quiz_attempts', 'session_type')) {
            return;
        }

        Schema::table('quiz_attempts', function (Blueprint $tableSchema) {
            $tableSchema->string('session_type', 32)->nullable()->after('study_mode');
        });
    }

    private function dropQuizAttemptSessionType(): void
    {
        if (!Schema::hasTable('quiz_attempts') || !Schema::hasColumn('quiz_attempts', 'session_type')) {
            return;
        }

        Schema::table('quiz_attempts', function (Blueprint $tableSchema) {
            $tableSchema->dropColumn('session_type');
        });
    }

    private function dropPlannerCourseUserColumns(): void
    {
        if (!Schema::hasTable('course_user')) {
            return;
        }

        if ($this->indexExists('course_user', 'idx_course_user_planner_repair_mode')) {
            Schema::table('course_user', function (Blueprint $tableSchema) {
                $tableSchema->dropIndex('idx_course_user_planner_repair_mode');
            });
        }

        $columns = [];
        if (Schema::hasColumn('course_user', 'planner_mistake_mastery_mode')) {
            $columns[] = 'planner_mistake_mastery_mode';
        }
        if (Schema::hasColumn('course_user', 'planner_repair_mode')) {
            $columns[] = 'planner_repair_mode';
        }

        if (!empty($columns)) {
            Schema::table('course_user', function (Blueprint $tableSchema) use ($columns) {
                $tableSchema->dropColumn($columns);
            });
        }
    }

    private function dropRedundantIndex(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        if (!$this->hasEquivalentIndexExcluding($table, $columns, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableSchema) use ($indexName) {
            $tableSchema->dropIndex($indexName);
        });
    }

    private function restoreIndex(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $tableSchema) use ($columns, $indexName) {
            $tableSchema->index($columns, $indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return array_key_exists($indexName, $this->getIndexDefinitions($table));
    }

    private function hasEquivalentIndexExcluding(string $table, array $columns, string $excludedIndexName): bool
    {
        foreach ($this->getIndexDefinitions($table) as $indexName => $definition) {
            if ($indexName === $excludedIndexName) {
                continue;
            }

            if (array_slice($definition, 0, count($columns)) === $columns) {
                return true;
            }
        }

        return false;
    }

    private function getIndexDefinitions(string $table): array
    {
        $connection = \Illuminate\Support\Facades\DB::connection();
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
        $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}`");
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
        $indexes = \Illuminate\Support\Facades\DB::select("PRAGMA index_list('{$tableLiteral}')");
        $definitions = [];

        foreach ($indexes as $index) {
            $indexName = $index->name ?? null;

            if ($indexName === null) {
                continue;
            }

            $indexLiteral = str_replace("'", "''", $indexName);
            $columns = \Illuminate\Support\Facades\DB::select("PRAGMA index_info('{$indexLiteral}')");

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
        $indexes = \Illuminate\Support\Facades\DB::select(
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
