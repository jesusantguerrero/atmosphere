<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-cycle "we already pushed this kind of nudge" flags. Three timestamps,
     * one per trigger, so each fires exactly once per billing cycle:
     *  - `nudged_post_cut_at`  — fired the day after the statement closed
     *    ("verify your new balance")
     *  - `nudged_pre_due_at`   — fired three days before the due date
     *    ("pay soon")
     *  - `nudged_overdue_at`   — fired ten days after the due date when the
     *    cycle still isn't recorded as paid ("did you forget to log it?")
     */
    public function up(): void
    {
        Schema::table('billing_cycles', function (Blueprint $table) {
            $table->timestamp('nudged_post_cut_at')->nullable()->after('status');
            $table->timestamp('nudged_pre_due_at')->nullable()->after('nudged_post_cut_at');
            $table->timestamp('nudged_overdue_at')->nullable()->after('nudged_pre_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('billing_cycles', function (Blueprint $table) {
            $table->dropColumn(['nudged_post_cut_at', 'nudged_pre_due_at', 'nudged_overdue_at']);
        });
    }
};
