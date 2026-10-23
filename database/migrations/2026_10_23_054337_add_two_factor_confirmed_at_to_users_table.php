<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'two_factor_confirmed_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });

        // Users who enabled 2FA before confirmation existed are already active.
        DB::table('users')
            ->whereNotNull('two_factor_secret')
            ->where('two_factor_secret', '!=', '')
            ->update(['two_factor_confirmed_at' => now()]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'two_factor_confirmed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('two_factor_confirmed_at');
            });
        }
    }
};
