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
        Schema::table('budget_months', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('id');
            $table->foreignId('user_id')->nullable()->after('team_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('budget_months', function (Blueprint $table) {
            $table->dropColumn('team_id');
            $table->dropColumn('user_id');
        });
    }
};
