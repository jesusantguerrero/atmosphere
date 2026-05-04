<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_targets', function (Blueprint $table) {
            $table->unsignedBigInteger('watchlist_id')->nullable()->after('target_type');
            $table->index('watchlist_id');
        });
    }

    public function down(): void
    {
        Schema::table('budget_targets', function (Blueprint $table) {
            $table->dropIndex(['watchlist_id']);
            $table->dropColumn('watchlist_id');
        });
    }
};
