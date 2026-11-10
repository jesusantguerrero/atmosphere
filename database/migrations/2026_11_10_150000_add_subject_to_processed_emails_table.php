<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processed_emails', function (Blueprint $table) {
            $table->string('subject', 255)->nullable()->after('message_id')->comment('Email subject, tells alert from notification');
        });
    }

    public function down(): void
    {
        Schema::table('processed_emails', function (Blueprint $table) {
            $table->dropColumn('subject');
        });
    }
};
