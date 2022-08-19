<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateTimeZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_zones', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('abbr')->nullable();
            $table->string('offset')->nullable();
            $table->string('isdst')->nullable();
            $table->string('text')->nullable();
            $table->json('utc')->nullable();
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
        Schema::drop('time_zones');
    }
}
