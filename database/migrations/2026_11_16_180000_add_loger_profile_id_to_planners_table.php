<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whose event it is (e.g. the kid's school activities). Null means the whole household.
     */
    public function up(): void
    {
        Schema::table('planners', function (Blueprint $table) {
            $table->foreignId('loger_profile_id')->nullable()->after('user_id')->constrained('loger_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('planners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loger_profile_id');
        });
    }
};
