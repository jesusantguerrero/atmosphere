<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlannerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('planners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_plan_id')->nullable();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->foreignId('dateable_id')->nullable();
            $table->string('dateable_type')->nullable();
            $table->json('data')->nullable();
            $table->enum('frequency', ['DAILY', 'WEEK_DAY', 'WEEK', 'MONTHLY']);
            $table->text('ical')->nullable();
            $table->date('date');
            $table->date('end_date');
            $table->boolean('automatic')->default(false);
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
        Schema::dropIfExists('planners');
    }
}
