<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budget_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->foreignId('team_id')->index();
            $table->foreignId('category_id')->index();
            $table->string('name');
            $table->string('color')->nullable();
            $table->text('icon')->nullable();
            $table->decimal('amount', 11, 2)->default(0);
            $table->enum('target_type', [
                'spending',
                'saving_balance',
                'savings_monthly',
                'debt_monthly_payment',
                'debt_payoff_date',
            ])->nullable();
            $table->enum('frequency', ['MONTHLY', 'WEEKLY', 'DATE'])->nullable();
            $table->integer('frequency_interval')->nullable();
            $table->string('frequency_interval_unit')->nullable();
            $table->integer('frequency_month_date')->nullable();
            $table->string('frequency_week_day')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', [
                'active',
                'paused',
            ])->default('active');
            $table->boolean('is_private')->default(false);
            $table->boolean('is_team_goal')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('budget_targets');
    }
}
