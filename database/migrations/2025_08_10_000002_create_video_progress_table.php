<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // We don't want this table; ensure it's removed if present
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('video_progress')) {
            Schema::drop('video_progress');
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('video_progress');
    }
};