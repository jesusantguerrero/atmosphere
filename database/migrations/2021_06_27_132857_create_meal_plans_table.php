<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMealPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_plan_id');
            $table->foreignId('datable_id')->nullable();
            $table->string('datable_type')->nullable();
            $table->json('data')->nullable();
            $table->enum('frequency', ['DAILY', 'WEEK_DAY', 'WEEK', 'MONTHLY']);
            $table->text('ical')->nullable();
            $table->date('date');
            $table->date('end_date');
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
        Schema::dropIfExists('meal_plans');
    }
}
