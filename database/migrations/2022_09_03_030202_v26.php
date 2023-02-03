<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('budget_targets', function (Blueprint $table) {
            $table->date('frequency_date')->nullable()->after('frequency_week_day');
            $table->index('target_type');
            $table->index('status');
        });

        Schema::table('budget_months', function (Blueprint $table) {
            $table->index('month');
            $table->index('team_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('budget_targets', function (Blueprint $table) {
            $table->dropColumn('frequency_date');
            $table->dropIndex('target_type');
            $table->dropIndex('status');
        });

        Schema::table('budget_months', function (Blueprint $table) {
            $table->dropIndex('month');
            $table->dropIndex('category_id');
            $table->dropIndex('team_id');
        });
    }
};
