<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Free-form fields for Planner rows that have no `dateable` polymorph
     * (school activities, errands, family TODOs).
     *
     * `source` is indexed so the Today widget can cheaply filter by origin
     * (e.g. "school", "work").
     */
    public function up(): void
    {
        Schema::table('planners', function (Blueprint $table) {
            $table->string('name')->nullable()->after('dateable_type');
            $table->text('notes')->nullable()->after('name');
            $table->decimal('total', 12, 2)->nullable()->after('notes');
            $table->string('source')->nullable()->after('total');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('planners', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn(['name', 'notes', 'total', 'source']);
        });
    }
};
