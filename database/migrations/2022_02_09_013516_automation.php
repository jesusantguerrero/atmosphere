<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->foreignId('integration_id')->nullable();
            $table->foreignId('trigger_id');
            $table->text('automatable_type')->nullable();
            $table->string('name');
            $table->text('description');
            $table->text('sentence');
            $table->json('config')->nullable();
            $table->json('track')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_background')->default(true);
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
