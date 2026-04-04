<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assignments') || !Schema::hasColumn('assignments', 'topic_id')) {
            return;
        }

        // Check if index already exists before adding
        $indexExists = false;
        $driver = DB::connection()->getDriverName();

        try {
            if ($driver === 'sqlite') {
                $indexes = DB::select("PRAGMA index_list('assignments')");
                foreach ($indexes as $index) {
                    if (str_contains($index->name ?? '', 'topic_id')) {
                        $indexExists = true;
                        break;
                    }
                }
            } else {
                $indexes = DB::select("SHOW INDEX FROM `assignments` WHERE Column_name = 'topic_id'");
                $indexExists = count($indexes) > 0;
            }
        } catch (\Throwable $e) {
            // If we can't check, try to add and let it fail gracefully
        }

        if (!$indexExists) {
            try {
                Schema::table('assignments', function (Blueprint $table) {
                    $table->index('topic_id', 'idx_assignments_topic_id');
                });
            } catch (\Throwable $e) {
                // Index may already exist under a different name (e.g. FK constraint index)
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('assignments')) {
            return;
        }

        try {
            Schema::table('assignments', function (Blueprint $table) {
                $table->dropIndex('idx_assignments_topic_id');
            });
        } catch (\Throwable $e) {
            // Index may not exist
        }
    }
};
