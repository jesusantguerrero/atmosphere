<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_targets', function (Blueprint $table) {
            // Loan target: the borrowed amount, its annual interest rate (percent),
            // the term in months and the date the loan starts amortizing. The
            // monthly payment itself is stored in the existing `amount` column so
            // the standard MONTHLY target funding logic keeps working unchanged;
            // these columns let the client recompute the payment and the payoff
            // schedule (remaining balance, months left) on the fly.
            $table->decimal('principal', 13, 2)->nullable()->after('amount');
            $table->decimal('interest_rate', 6, 3)->nullable()->after('principal');
            $table->integer('term_months')->nullable()->after('interest_rate');
            $table->date('loan_start_date')->nullable()->after('term_months');
        });

        // Doctrine DBAL has no portable ENUM modifier, so a raw ALTER is required.
        // Keep every existing value and append 'loan'.
        DB::statement("ALTER TABLE budget_targets MODIFY COLUMN target_type ENUM(
            'spending',
            'saving_balance',
            'savings_monthly',
            'debt_monthly_payment',
            'debt_payoff_date',
            'challenge_under_amount',
            'loan'
        ) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE budget_targets MODIFY COLUMN target_type ENUM(
            'spending',
            'saving_balance',
            'savings_monthly',
            'debt_monthly_payment',
            'debt_payoff_date',
            'challenge_under_amount'
        ) NULL");

        Schema::table('budget_targets', function (Blueprint $table) {
            $table->dropColumn(['principal', 'interest_rate', 'term_months', 'loan_start_date']);
        });
    }
};
