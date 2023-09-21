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
            $table->string('currency_code')->default('DOP')->after('name');
            $table->decimal('activity')->default(0)->after('budgeted');
            $table->decimal('available')->default(0)->after('activity');
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
            $table->dropColumn('currency_code');
            $table->dropColumn('activity');
            $table->dropColumn('available');
        });
    }
};
