<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_domains', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Insert default domains
        DB::table('medical_domains')->insert([
            [
                'slug' => 'anatomy',
                'name' => 'Anatomy',
                'description' => 'Study of body structure and organization',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'slug' => 'physiology',
                'name' => 'Physiology',
                'description' => 'Study of normal body functions',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'slug' => 'biochemistry',
                'name' => 'Biochemistry',
                'description' => 'Study of chemical processes in living organisms',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'slug' => 'histology',
                'name' => 'Histology',
                'description' => 'Study of tissues at microscopic level',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_domains');
    }
};
