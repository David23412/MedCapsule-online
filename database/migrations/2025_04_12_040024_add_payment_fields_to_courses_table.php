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
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency')->default('EGP');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('is_paid');
            $table->dropColumn('price');
            $table->dropColumn('currency');
        });
    }
};
