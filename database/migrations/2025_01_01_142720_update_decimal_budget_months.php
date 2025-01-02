<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('budget_months', function (Blueprint $table) {
            $table->decimal('budgeted', 16, 4)->default(0)->change();
            $table->decimal('activity', 16, 4)->default(0)->change();
            $table->decimal('accounts_balance', 16, 4)->default(0)->change();
            $table->decimal('funded_spending', 16, 4)->default(0)->change();
            $table->decimal('left_from_last_month', 16, 4)->default(0)->change();
            $table->decimal('payments', 16, 4)->default(0)->change();
            $table->decimal('moved_from_last_month', 16, 4)->default(0)->change();
            $table->decimal('overspending_previous_month', 16, 4)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_months', function (Blueprint $table) {
            $table->decimal('budgeted')->default(0)->change();
            $table->decimal('activity')->default(0)->change();
            $table->decimal('accounts_balance')->default(0)->change();
            $table->decimal('funded_spending')->default(0)->change();
            $table->decimal('left_from_last_month')->default(0)->change();
            $table->decimal('payments')->default(0)->change();
            $table->decimal('moved_from_last_month')->default(0)->change();
            $table->decimal('overspending_previous_month')->default(0)->change();
        });
    }
};
