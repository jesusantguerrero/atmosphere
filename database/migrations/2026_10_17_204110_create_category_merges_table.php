<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_merges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('target_id');
            $table->string('source_name');
            $table->string('target_name');
            $table->longText('snapshot');
            $table->json('references');
            $table->string('after_hash', 64);
            $table->timestamp('undone_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_merges');
    }
};
