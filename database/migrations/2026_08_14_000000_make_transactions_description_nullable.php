<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saving a transaction without a description used to 500 with
     * "SQLSTATE[23000]: Column 'description' cannot be null" because the column
     * was NOT NULL while the UI treats description as optional. Make it nullable
     * so the two agree; the app also backfills a payee-based default.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('description', 200)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('description', 200)->nullable(false)->change();
        });
    }
};
