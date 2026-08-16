<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user delivery preferences for the notification system. Nullable JSON so
 * existing users fall back to the defaults defined on the User model
 * (`User::defaultNotificationPrefs()`) — email + push both ON — without a
 * backfill. Shape: { "email": bool, "push": bool }.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_prefs')->nullable()->after('language');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_prefs');
        });
    }
};
