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
        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');

            $table->foreignId('labelable_group_id')->nullable();
            $table->foreignId('labelable_id')->nullable();
            $table->text('labelable_group_type')->nullable();
            $table->text('labelable_type')->nullable();

            $table->string('name');
            $table->string('label');
            $table->string('color');

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
        Schema::dropIfExists('labels');
    }
};
