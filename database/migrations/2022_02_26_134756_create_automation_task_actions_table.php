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
        Schema::create('automation_task_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('automation_id');
            $table->foreignId('automation_task_id');
            $table->string('name');
            $table->text('entity');
            $table->integer('order')->default(0);
            $table->enum('task_type', [
                'trigger',
                'action',
                'component',
            ]);
            $table->json('values')->nullable();
            $table->boolean('accepts_config')->default(false);
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
        Schema::dropIfExists('automation_task_actions');
    }
};
