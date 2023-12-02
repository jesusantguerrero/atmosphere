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
        Schema::table('budget_targets', function (Blueprint $table) {
            $table->date('completed_at')->nullable();
        });

        Schema::table('budget_months', function (Blueprint $table) {
            $table->decimal('left_from_last_month')->default(0)->after('payments');
            $table->decimal('overspending_previous_month')->default(0)->after('left_from_last_month');
        });
    }
};
