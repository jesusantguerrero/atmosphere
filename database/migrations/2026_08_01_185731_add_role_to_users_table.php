<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce a `role` column on users to support multi-admin access to the
 * backoffice without depending on spatie/laravel-permission. Three tiers:
 *
 *   - `super_admin` — instance owner. Reserved for destructive operations
 *     (delete team, hard-delete user, kill-switch feature flags). Backfilled
 *     from the historic APP_SUPER_ADMIN env var so existing behavior stays.
 *   - `admin` — staff with day-to-day backoffice access (view users, view
 *     teams, impersonate for support, toggle non-destructive feature flags).
 *   - `user` — everyone else. Default.
 *
 * A dedicated seeder (RoleBackfillSeeder) syncs the APP_SUPER_ADMIN email to
 * `super_admin` on migrate so no manual step is required.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', ['super_admin', 'admin', 'user'])
                ->default('user')
                ->after('email')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }
};
