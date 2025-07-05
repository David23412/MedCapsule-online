<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            // Add question_type field and default existing questions to 'multiple_choice'
            $table->string('question_type')->default('multiple_choice')->after('image_url');
            
            // Change correct_answer from enum to text to support longer written answers
            $table->text('correct_answer')->change();
            
            // Add field for alternative correct answers (JSON field to store variations)
            $table->json('alternative_answers')->nullable()->after('correct_answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
            $table->dropColumn('alternative_answers');
            
            // This might cause issues if there are already written answers stored
            // that don't fit the original enum, so we'll just note that here
            // $table->enum('correct_answer', ['A', 'B', 'C', 'D'])->change();
        });
    }
}; 