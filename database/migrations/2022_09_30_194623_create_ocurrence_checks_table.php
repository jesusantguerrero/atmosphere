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
        Schema::create('occurrence_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->string('name');
            $table->string('label');
            $table->date('last_date')->nullable();
            $table->integer('occurrence_count')->default(0);
            $table->integer('total_days')->default(0);
            $table->integer('previous_days_count')->default(0);
            $table->integer('avg_days_passed')->default(0);
            $table->boolean('notify_on_avg')->default(false);
            $table->boolean('notify_on_last_count')->default(false);;
            $table->boolean('is_active')->default(false);
            $table->json('log')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('occurrence_checks');
    }
};
