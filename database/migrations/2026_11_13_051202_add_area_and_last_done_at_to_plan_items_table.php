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
        Schema::table('plan_items', function (Blueprint $table) {
            $table->string('area', 40)->nullable()->index();
            $table->timestamp('last_done_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_items', function (Blueprint $table) {
            $table->dropIndex(['area']);
            $table->dropColumn(['area', 'last_done_at']);
        });
    }
};
