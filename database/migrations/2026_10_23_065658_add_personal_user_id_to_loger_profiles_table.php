<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loger_profiles', function (Blueprint $table): void {
            $table->unsignedBigInteger('personal_user_id')->nullable();
            $table->unique(['team_id', 'personal_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('loger_profiles', function (Blueprint $table): void {
            $table->dropUnique(['team_id', 'personal_user_id']);
            $table->dropColumn('personal_user_id');
        });
    }
};
