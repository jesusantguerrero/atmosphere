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
        Schema::create('automation_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_service_id');
            $table->enum('task_type', [
                'trigger',
                'action',
                'component',
            ]);
            $table->text('entity');
            $table->string('name');
            $table->string('label');
            $table->string('description')->nullable();
            $table->json('fields')->nullable();
            $table->json('config')->nullable();
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
        Schema::dropIfExists('automation_tasks');
    }
};
