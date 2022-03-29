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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->string('name');
            $table->decimal('target', 11, 2)->default(0);
            $table->decimal('amount', 11, 2)->default(0);
            $table->date('due_date');
            $table->string('color');
            $table->text('icon');
            $table->text('note')->nullable();
            $table->boolean('is_team_goal')->default(false);
            $table->enum('status', [
                'active',
                'paused',
                'reached',
            ])->default('active');
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
        Schema::dropIfExists('goals');
    }
};
