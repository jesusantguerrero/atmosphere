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
        Schema::create('billing_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->foreignId('account_id');
            $table->date('start_at');
            $table->date('end_at');
            $table->date('due_at');
            $table->decimal('minimum_payment', 11, 4)->default(0);;
            $table->decimal('debt', 11, 4)->default(0);;
            $table->decimal('paid', 11, 4)->default(0);;
            $table->decimal('subtotal', 11, 4)->default(0);;
            $table->decimal('discount', 11, 4)->default(0);;
            $table->decimal('total', 11, 4)->default(0);;
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
