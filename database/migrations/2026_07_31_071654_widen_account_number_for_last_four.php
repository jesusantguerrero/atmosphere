<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `accounts.number` holds the card's visible last four digits, but the journal
 * package declares it as an integer, so `0037` was being stored as `37`.
 *
 * Widening it to a string keeps the leading zeros. Existing values are padded
 * back to four characters on the way in: the field is always exactly the last
 * four digits, so `37` unambiguously means `0037` and nothing has to be retyped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('number', 20)->nullable()->change();
        });

        DB::table('accounts')
            ->whereNotNull('number')
            ->where('number', '!=', '')
            ->update([
                'number' => DB::raw("LPAD(number, 4, '0')"),
            ]);
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->integer('number')->nullable()->change();
        });
    }
};
