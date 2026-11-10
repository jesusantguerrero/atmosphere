<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How many `frequency` units between occurrences: 4 with WEEK repeats every 4 weeks.
     * Existing rows keep repeating every unit.
     */
    public function up(): void
    {
        Schema::table('planners', function (Blueprint $table) {
            $table->unsignedSmallInteger('frequency_interval')->default(1)->after('frequency');
        });
    }

    public function down(): void
    {
        Schema::table('planners', function (Blueprint $table) {
            $table->dropColumn('frequency_interval');
        });
    }
};
