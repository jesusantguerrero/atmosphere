<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widen `budget_months.available` from the default decimal(8,2) — which
 * silently truncates at 999,999.99 — to decimal(16,4).
 *
 * The 2025_01_01_142720 migration widened every other numeric column on
 * this table (budgeted, activity, accounts_balance, funded_spending,
 * left_from_last_month, payments, moved_from_last_month,
 * overspending_previous_month) but missed `available`. Users with large
 * category balances (e.g., savings envelopes above 1M in DOP) saw
 * their available capped at "999,999.99" while assigned/activity
 * displayed correctly.
 *
 * Also widens the sibling numeric columns on `budget_movements` for
 * consistency — they still had the old (8,2) default and would truncate
 * the same way once movements pushed the balance past 1M.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_months', function (Blueprint $table) {
            $table->decimal('available', 16, 4)->default(0)->change();
        });

        Schema::table('budget_movements', function (Blueprint $table) {
            $table->decimal('amount', 16, 4)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('budget_months', function (Blueprint $table) {
            $table->decimal('available')->default(0)->change();
        });

        Schema::table('budget_movements', function (Blueprint $table) {
            $table->decimal('amount')->default(0)->change();
        });
    }
};
