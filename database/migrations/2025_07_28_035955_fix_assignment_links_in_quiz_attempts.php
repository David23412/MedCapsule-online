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
        // Link existing assignment quiz attempts to the correct assignments
        // Based on the data: Basics (ID 1) should have the 100% attempt, Basics 2 (ID 3) should have the 0% attempts
        
        // Link the 100% attempt (ID 10) to Basics (ID 1)
        DB::table('quiz_attempts')
            ->where('id', 10)
            ->update(['assignment_id' => 1]);
            
        // Link the 0% attempts (IDs 11, 12, 13) to Basics 2 (ID 3)
        DB::table('quiz_attempts')
            ->whereIn('id', [11, 12, 13])
            ->update(['assignment_id' => 3]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove assignment links
        DB::table('quiz_attempts')
            ->whereIn('id', [10, 11, 12, 13])
            ->update(['assignment_id' => null]);
    }
};
