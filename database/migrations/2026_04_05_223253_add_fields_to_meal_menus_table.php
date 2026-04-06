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
        Schema::table('meal_menus', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id');
            $table->foreignId('user_id')->after('team_id');
            $table->string('name')->after('user_id');
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_template')->default(false)->after('description');
        });

        Schema::table('meal_plans', function (Blueprint $table) {
            $table->foreignId('menu_id')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->dropColumn('menu_id');
        });

        Schema::table('meal_menus', function (Blueprint $table) {
            $table->dropColumn(['team_id', 'user_id', 'name', 'description', 'is_template']);
        });
    }
};
