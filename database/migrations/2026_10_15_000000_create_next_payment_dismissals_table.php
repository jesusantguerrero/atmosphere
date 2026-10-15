<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Ya lo pagué en otra cuenta" — lets the user dismiss a synthetic
        // next-payment for one month without creating a fake transaction.
        // Keyed by the payment's synthetic id + period; recurring items
        // reappear the following month.
        Schema::create('next_payment_dismissals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('payment_key');   // e.g. budget_3, cc_payment_1735_2026-11, a planned txn id
            $table->string('period', 7);     // YYYY-MM the dismissal applies to
            $table->timestamps();

            $table->unique(['team_id', 'payment_key', 'period']);
            $table->index(['team_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('next_payment_dismissals');
    }
};
