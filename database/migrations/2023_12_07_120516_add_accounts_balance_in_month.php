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
            $table->decimal('accounts_balance')->default(0)->after('left_from_last_month');
            $table->decimal('overspending_from_last_month')->default(0);
            $table->json('meta_data')->nullable();
        });
    }
};
