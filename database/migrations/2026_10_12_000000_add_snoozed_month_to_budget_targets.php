<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_targets', function (Blueprint $table) {
            // YYYY-MM this target's underfunded (amber) alert is snoozed for.
            // YNAB-style "Snooze Target": silences the alert for that one month
            // without touching the long-term target. Null = not snoozed.
            $table->string('snoozed_month', 7)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('budget_targets', function (Blueprint $table) {
            $table->dropColumn('snoozed_month');
        });
    }
};
