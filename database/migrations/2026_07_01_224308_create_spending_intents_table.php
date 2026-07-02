<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `spending_intents` — lightweight "how much do I intend to spend on X
 * this month" table, deliberately independent of budget_targets and
 * budget_months.
 *
 * Motivation: when the user is behind on reconciliation, the numbers in
 * the Budget view can't be trusted for the current month, but they still
 * want to be intentional about spending. This table lets them declare an
 * intention per category per month without touching the accounting
 * pipeline.
 *
 * `month` is stored as a first-day-of-month DATE so range queries and
 * date_trunc-style grouping stay simple, and a unique index keeps one
 * intent per (team, category, month).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('spending_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->date('month');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'category_id', 'month'], 'spending_intents_scope_unique');
            $table->index(['team_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spending_intents');
    }
};
