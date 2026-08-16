<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payee aliases: map an incoming payee name (e.g. from a recurring bank import)
 * to a canonical payee, so a name that was previously merged away resolves back
 * to its target instead of being re-created as a fresh duplicate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payee_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->string('name');            // the incoming/alias name
            $table->foreignId('payee_id');     // canonical target payee
            $table->timestamps();
            $table->unique(['team_id', 'name']);
            $table->index('payee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payee_aliases');
    }
};
