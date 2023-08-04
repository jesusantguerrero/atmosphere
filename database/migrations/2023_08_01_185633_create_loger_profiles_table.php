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
        Schema::create('loger_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->string("name");
            $table->text("image_url")->nullable();
            $table->text("background_url")->nullable();
            $table->text("cover_url")->nullable();
            $table->text("description")->nullable();
            $table->boolean("is_favorite")->default(false);
            $table->integer("index")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loger_profiles');
    }
};
