<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Automation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('automatable_id')->nullable();
            $table->foreignId('automation_recipe_id')->nullable();
            $table->foreignId('integration_id');
            $table->text('automatable_type')->nullable();
            $table->string('name');
            $table->text('description');
            $table->text('sentence');
            $table->json('config')->nullable();
            $table->json('track')->nullable();
            $table->boolean('status')->default(true);
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
        Schema::dropIfExists('automations');
    }
}
