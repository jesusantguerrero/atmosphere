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
        //
        Schema::create('goal_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->text('description');
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('budget_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id');
            $table->string('month');
            $table->string('name');
            $table->decimal('budgeted', 11, 4)->default(0);
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
        //
        Schema::dropIfExists('goal_types');
        Schema::dropIfExists('budget_months');
    }
};
