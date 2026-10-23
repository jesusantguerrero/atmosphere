<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_targets', function (Blueprint $table) {
            // A target the user took down. The row (and its history) stays; it just stops being the category's target.
            // Null = live. BudgetTarget hides archived rows from every query by default.
            $table->timestamp('archived_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('budget_targets', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
