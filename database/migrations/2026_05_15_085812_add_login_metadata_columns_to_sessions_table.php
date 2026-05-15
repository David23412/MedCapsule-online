<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        Schema::table('sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('sessions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->index();
            }

            if (! Schema::hasColumn('sessions', 'fingerprint')) {
                $table->string('fingerprint')->nullable();
            }

            if (! Schema::hasColumn('sessions', 'is_visitor')) {
                $table->boolean('is_visitor')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        Schema::table('sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sessions', 'is_visitor')) {
                $table->dropColumn('is_visitor');
            }

            if (Schema::hasColumn('sessions', 'fingerprint')) {
                $table->dropColumn('fingerprint');
            }

            if (Schema::hasColumn('sessions', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
