<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'challenge_under_amount' to the existing target_type enum (WL-7).
        // Doctrine DBAL doesn't ship a portable ENUM modifier, so a raw ALTER is required.
        DB::statement("ALTER TABLE budget_targets MODIFY COLUMN target_type ENUM(
            'spending',
            'saving_balance',
            'savings_monthly',
            'debt_monthly_payment',
            'debt_payoff_date',
            'challenge_under_amount'
        ) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE budget_targets MODIFY COLUMN target_type ENUM(
            'spending',
            'saving_balance',
            'savings_monthly',
            'debt_monthly_payment',
            'debt_payoff_date'
        ) NULL");
    }
};
