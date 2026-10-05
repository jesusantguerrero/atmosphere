<?php

namespace Tests\Unit;

use App\Domains\Transaction\Services\ReconciliationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Insane\Journal\Models\Core\Account;
use Tests\TestCase;

class PendingReconciliationDifferenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'reconciliation_test', 'database.connections.reconciliation_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('reconciliations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('account_id');
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->string('status');
            $table->integer('team_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->integer('id');
            $table->string('status');
            $table->date('date')->nullable();
        });
        Schema::create('transaction_lines', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('transaction_id');
            $table->integer('account_id');
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->integer('type');
            $table->integer('team_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->boolean('matched')->default(false);
            $table->timestamps();
        });
        Schema::create('reconciliation_entries', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('reconciliation_id');
            $table->integer('transaction_id');
            $table->integer('transaction_line_id');
            $table->integer('team_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->boolean('matched')->default(false);
            $table->timestamps();
        });
    }

    public function test_pending_difference_uses_verified_ledger_at_statement_date_without_writing(): void
    {
        DB::table('reconciliations')->insert([
            ['id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 20, 'difference' => 999, 'status' => 'pending'],
            ['id' => 2, 'account_id' => 20, 'date' => '2026-08-12', 'amount' => 15, 'difference' => 99, 'status' => 'pending'],
            ['id' => 3, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 0, 'difference' => 0, 'status' => 'completed'],
        ]);
        DB::table('transactions')->insert([['id' => 1, 'status' => 'verified'], ['id' => 2, 'status' => 'draft']]);
        DB::table('transaction_lines')->insert([
            ['transaction_id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 100, 'type' => 1],
            ['transaction_id' => 1, 'account_id' => 10, 'date' => '2026-08-11', 'amount' => 30, 'type' => -1],
            ['transaction_id' => 1, 'account_id' => 10, 'date' => '2026-08-13', 'amount' => 500, 'type' => 1],
            ['transaction_id' => 2, 'account_id' => 10, 'date' => '2026-08-11', 'amount' => 700, 'type' => 1],
            ['transaction_id' => 1, 'account_id' => 99, 'date' => '2026-08-11', 'amount' => 900, 'type' => 1],
        ]);
        DB::enableQueryLog();
        $differences = (new ReconciliationService)->pendingDifferences([1, 2, 3]);
        $this->assertSame(50.0, $differences->get(1));
        $this->assertSame(-15.0, $differences->get(2));
        $this->assertFalse($differences->has(3));
        $this->assertCount(1, DB::getQueryLog());
        $account = new Account;
        $account->id = 10;
        $this->assertSame((new ReconciliationService)->balanceAsOf($account, '2026-08-12') - 20, $differences->get(1));
        $this->assertEquals(999, DB::table('reconciliations')->where('id', 1)->value('difference'));
        $this->assertTrue((new ReconciliationService)->pendingDifferences([])->isEmpty());
    }
}
