<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_targets', function (Blueprint $table) {
            // A bill reminder that followed a new price keeps the old amount and when it changed.
            $table->decimal('previous_amount', 11, 2)->nullable()->after('amount');
            $table->timestamp('amount_changed_at')->nullable()->after('previous_amount');
        });
    }

    public function down(): void
    {
        Schema::table('budget_targets', function (Blueprint $table) {
            $table->dropColumn(['previous_amount', 'amount_changed_at']);
        });
    }
};
