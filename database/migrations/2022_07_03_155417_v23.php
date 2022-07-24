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
        Schema::table('month_budgets', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id');
            $table->foreignId('user_id')->after('team_id');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('month_budgets', function (Blueprint $table) {
            $table->dropColumn('team_id');
            $table->dropColumn('user_id');
        });
    }
};
