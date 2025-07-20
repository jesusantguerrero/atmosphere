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
        Schema::create('currency_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->foreignId('account_id');
            $table->string('currency_code', 4);
            $table->decimal('balance', 16, 4)->default(0);
            $table->decimal('pending_balance', 16, 4)->default(0);
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();

            // Indexes for performance
            $table->index(['account_id', 'currency_code']);
            $table->index(['team_id', 'currency_code']);
            
            // Foreign key constraints
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate currency balances per account
            $table->unique(['account_id', 'currency_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_balances');
    }
};
