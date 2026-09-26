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
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedTinyInteger('credit_renewal_month')->nullable()->after('credit_payment_days');
            $table->decimal('credit_annual_fee', 19, 4)->nullable()->after('credit_renewal_month');
            $table->decimal('credit_monthly_insurance', 19, 4)->nullable()->after('credit_annual_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['credit_renewal_month', 'credit_annual_fee', 'credit_monthly_insurance']);
        });
    }
};
