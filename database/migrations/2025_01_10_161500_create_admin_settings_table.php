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
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('admin_settings')->insert([
            [
                'key' => 'fake_live_users_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable fake live user counter for social proof',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fake_live_users_min',
                'value' => '87',
                'type' => 'integer',
                'description' => 'Minimum fake live user count',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fake_live_users_max',
                'value' => '143',
                'type' => 'integer',
                'description' => 'Maximum fake live user count',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fake_students_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable fake active medical students counter',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fake_students_count',
                'value' => '2847',
                'type' => 'integer',
                'description' => 'Fake active medical students count',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};
