<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a guest member may see in a household. A guest sees nothing that has no grant here.
     */
    public function up(): void
    {
        Schema::create('team_member_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module', 32);
            $table->foreignId('profile_id')->nullable()->constrained('loger_profiles')->cascadeOnDelete();
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->enum('access', ['read', 'write'])->default('read');
            $table->timestamps();

            $table->index(['team_id', 'user_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_member_grants');
    }
};
