<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AutomationRecipes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('automation_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_service_id');
            $table->foreignId('team_id');
            $table->string('name');
            $table->string('service_name');
            $table->text('description');
            $table->text('sentence');
            $table->json('mapper');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('automation_recipes');
    }
}
