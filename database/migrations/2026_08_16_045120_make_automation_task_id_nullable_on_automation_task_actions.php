<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Automations built in code (the "emails -> tasks" toggle, the bank setup
 * commands) reference their action by class name and have no catalog row to
 * point `automation_task_id` at, so the insert died on a fresh schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_task_actions', function (Blueprint $table) {
            $table->foreignId('automation_task_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('automation_task_actions', function (Blueprint $table) {
            $table->foreignId('automation_task_id')->nullable(false)->change();
        });
    }
};
