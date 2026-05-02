<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('goals');
    }

    public function down(): void
    {
        // Goals were superseded by BudgetTarget; this table is intentionally not recreated.
    }
};
