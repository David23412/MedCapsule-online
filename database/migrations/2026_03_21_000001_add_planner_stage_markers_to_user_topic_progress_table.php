<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_topic_progress', function (Blueprint $table) {
            if (!Schema::hasColumn('user_topic_progress', 'planner_tutor_cleared_at')) {
                $table->timestamp('planner_tutor_cleared_at')->nullable()->after('last_attempt_date');
            }

            if (!Schema::hasColumn('user_topic_progress', 'planner_quiz_cleared_at')) {
                $table->timestamp('planner_quiz_cleared_at')->nullable()->after('planner_tutor_cleared_at');
            }

            if (!Schema::hasColumn('user_topic_progress', 'planner_cleared_at')) {
                $table->timestamp('planner_cleared_at')->nullable()->after('planner_quiz_cleared_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_topic_progress', function (Blueprint $table) {
            $dropColumns = array_values(array_filter([
                Schema::hasColumn('user_topic_progress', 'planner_tutor_cleared_at') ? 'planner_tutor_cleared_at' : null,
                Schema::hasColumn('user_topic_progress', 'planner_quiz_cleared_at') ? 'planner_quiz_cleared_at' : null,
                Schema::hasColumn('user_topic_progress', 'planner_cleared_at') ? 'planner_cleared_at' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
