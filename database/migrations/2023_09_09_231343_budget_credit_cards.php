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
            $table->decimal('funded_spending')->default(0)->after('available');
            $table->decimal('payments')->default(0)->after('funded_spending');
            $table->decimal('moved_from_last_month')->default(0)->after('funded_spending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_months', function (Blueprint $table) {
            $table->dropColumn('funded_spending');
            $table->dropColumn('payments');
            $table->dropColumn('moved_from_last_month');
        });
    }
};
