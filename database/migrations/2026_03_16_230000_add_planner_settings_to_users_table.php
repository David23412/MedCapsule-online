<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'target_score')) {
                $table->unsignedTinyInteger('target_score')->default(80)->after('exam_date');
            }

            if (!Schema::hasColumn('users', 'daily_study_minutes')) {
                $table->unsignedSmallInteger('daily_study_minutes')->default(90)->after('target_score');
            }

            if (!Schema::hasColumn('users', 'planner_focus')) {
                $table->string('planner_focus')->default('balanced')->after('daily_study_minutes');
            }

            if (!Schema::hasColumn('users', 'repair_mode')) {
                $table->string('repair_mode', 20)->nullable()->after('planner_focus');
            }

            if (!Schema::hasColumn('users', 'classic_repair_difficulty')) {
                $table->string('classic_repair_difficulty', 20)->nullable()->after('repair_mode');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('users', 'planner_focus')) {
                $columns[] = 'planner_focus';
            }

            if (Schema::hasColumn('users', 'classic_repair_difficulty')) {
                $columns[] = 'classic_repair_difficulty';
            }

            if (Schema::hasColumn('users', 'repair_mode')) {
                $columns[] = 'repair_mode';
            }

            if (Schema::hasColumn('users', 'daily_study_minutes')) {
                $columns[] = 'daily_study_minutes';
            }

            if (Schema::hasColumn('users', 'target_score')) {
                $columns[] = 'target_score';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
