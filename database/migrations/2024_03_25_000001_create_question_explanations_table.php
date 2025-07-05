<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('question_explanations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->text('detailed_explanation');
            $table->text('key_points')->nullable();
            $table->text('related_concepts')->nullable();
            $table->json('answer_statistics')->nullable();
            $table->text('references')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('question_explanations');
    }
}; 