<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meal_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->foreignId('menu_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('meals', function (Blueprint $table) {
            $table->foreignId('meal_type_id')->nullable()->after('user_id');
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meal_types');
        Schema::table('meals', function (Blueprint $table) {
            $table->dropColumn('meal_type_id')->after('user_id');
            $table->dropColumn('notes');
        });
    }
};
