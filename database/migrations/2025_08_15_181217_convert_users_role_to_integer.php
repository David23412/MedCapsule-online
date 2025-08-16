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
        // Step 1: Normalize existing string values to integers (as strings first)
        DB::statement("
            UPDATE users SET role = CASE 
                WHEN LOWER(TRIM(role)) = 'admin' THEN '1'
                WHEN LOWER(TRIM(role)) = 'tutor' THEN '2' 
                WHEN LOWER(TRIM(role)) = 'student' THEN '3'
                ELSE '3'
            END
        ");
        
        // Step 2: Convert column type to integer in-place
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('role')->unsigned()->default(3)->change();
            // Only add index if it doesn't exist
            if (!Schema::hasIndex('users', 'idx_users_role')) {
                $table->index('role', 'idx_users_role');
            }
        });
        
        // Step 3: Add check constraint for data integrity
        DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN (1, 2, 3))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Remove check constraint
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_users_role');
        
        // Step 2: Drop index
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
        });
        
        // Step 3: Convert back to string values
        DB::statement("
            UPDATE users SET role = CASE 
                WHEN role = 1 THEN 'admin'
                WHEN role = 2 THEN 'tutor'
                WHEN role = 3 THEN 'student'
                ELSE 'student'
            END
        ");
        
        // Step 4: Convert column type back to string
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->change();
        });
    }
};