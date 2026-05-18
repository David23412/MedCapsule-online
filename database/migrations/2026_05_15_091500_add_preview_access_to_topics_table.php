<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('topics') || Schema::hasColumn('topics', 'is_preview_enabled')) {
            return;
        }

        Schema::table('topics', function (Blueprint $table) {
            $table->boolean('is_preview_enabled')->default(false)->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('topics') || ! Schema::hasColumn('topics', 'is_preview_enabled')) {
            return;
        }

        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn('is_preview_enabled');
        });
    }
};
