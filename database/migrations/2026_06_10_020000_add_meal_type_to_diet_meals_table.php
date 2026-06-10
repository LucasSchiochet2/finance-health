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
        Schema::table('diet_meals', function (Blueprint $table) {
            $table->string('meal_type', 30)
                ->default('extra')
                ->after('date');

            $table->index(['user_id', 'meal_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diet_meals', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'meal_type']);
            $table->dropColumn('meal_type');
        });
    }
};
