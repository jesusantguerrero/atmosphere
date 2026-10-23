<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether a free-form planner row is an `event` or a `task`. Null on rows created before this existed:
     * the calendar keeps reading those as tasks.
     */
    public function up(): void
    {
        Schema::table('planners', function (Blueprint $table) {
            $table->string('kind', 20)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('planners', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
