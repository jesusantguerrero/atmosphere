<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('meal_type_id');
            $table->integer('prep_minutes')->default(0);
            $table->integer('cook_minutes')->default(0);
            $table->integer('servings')->default(0);
            $table->integer('rating')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
