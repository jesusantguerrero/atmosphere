<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processed_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('gmail_id', 64)->comment('Gmail API message ID');
            $table->string('message_id', 512)->nullable()->comment('RFC 822 Message-ID header');
            $table->unsignedBigInteger('automation_id')->nullable()->index();
            $table->unsignedBigInteger('transaction_id')->nullable()->index();
            $table->string('status', 20)->default('processed');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['team_id', 'gmail_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_emails');
    }
};
