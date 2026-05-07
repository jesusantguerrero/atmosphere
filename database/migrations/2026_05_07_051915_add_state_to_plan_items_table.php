<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds 3-state for shopping-list usage: `pending` (default), `buy`, `skip`.
     * Existing 2-state callers keep working via `is_done` ↔ `state == 'buy'`
     * sync inside the PlanItem model.
     *
     * Backfills existing rows: any item with `is_done = 1` becomes `buy`.
     */
    public function up(): void
    {
        Schema::table('plan_items', function (Blueprint $table) {
            $table->string('state', 16)->default('pending')->after('is_done');
        });

        DB::table('plan_items')->where('is_done', true)->update(['state' => 'buy']);
    }

    public function down(): void
    {
        Schema::table('plan_items', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
