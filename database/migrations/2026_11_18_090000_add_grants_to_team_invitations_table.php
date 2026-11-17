<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profiles a guest invitation opens once accepted: [{ profile_id, access }].
     */
    public function up(): void
    {
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->json('grants')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->dropColumn('grants');
        });
    }
};
