<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('budget_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->foreignId('watchlist_team_id');
            $table->foreignId('watchlist_id');
            $table->foreignId('category_team_id');
            $table->foreignId('category_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('meta_data')->nullable();
            $table->integer('index')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_funds');
    }
};
