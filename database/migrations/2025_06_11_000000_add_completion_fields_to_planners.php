<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('planners', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable();
            $table->foreignId('completed_transaction_id')->nullable();
            $table->string('completion_notes')->nullable();
            $table->integer('completed_resource_id')->nullable();
            $table->string('completed_resource_type')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled', 'planned'])->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('planners', function (Blueprint $table) {
            $table->dropColumn([
                'completed_at',
                'completed_by',
                'completed_transaction_id',
                'completion_notes',
                'completed_resource_id',
                'completed_resource_type',
                'status'
            ]);
        });
    }
}; 