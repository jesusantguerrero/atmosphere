<?php

namespace Tests\Unit;

use App\Domains\Transaction\Data\ReconciliationParamsData;
use App\Domains\Transaction\Http\Controllers\ReconciliationController;
use App\Domains\Transaction\Listeners\UpdateOpenReconciliations;
use App\Domains\Transaction\Services\ReconciliationService;
use App\Http\Requests\ReconciliationEntryRequest;
use App\Http\Requests\ReconciliationRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReconciliationWorkflowTest extends PendingReconciliationDifferenceTest
{
    public function test_pull_new_preserves_reviewed_entries_and_recalculates_difference(): void
    {
        DB::table('reconciliations')->insert(['id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 20, 'difference' => 999, 'status' => 'pending']);
        DB::table('transactions')->insert(['id' => 1, 'status' => 'verified', 'date' => '2026-08-12']);
        DB::table('transaction_lines')->insert([
            ['id' => 1, 'transaction_id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 100, 'type' => 1],
            ['id' => 2, 'transaction_id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 30, 'type' => -1],
        ]);
        DB::table('reconciliation_entries')->insert(['reconciliation_id' => 1, 'transaction_id' => 1, 'transaction_line_id' => 1, 'matched' => true]);
        $account = new Account;
        $account->id = 10;
        $reconciliation = Reconciliation::findOrFail(1)->setRelation('account', $account);
        $service = new ReconciliationService;
        $service->syncTransactions($reconciliation);
        $this->assertTrue((bool) $reconciliation->entries()->where('transaction_line_id', 1)->value('matched'));
        $this->assertFalse((bool) $reconciliation->entries()->where('transaction_line_id', 2)->value('matched'));
        $this->assertEquals(50, $reconciliation->fresh()->difference);
        $service->syncTransactions($reconciliation);
        $this->assertSame(2, $reconciliation->entries()->count());
        $service->update($reconciliation, new ReconciliationParamsData(10, 70, '2026-08-12', 1));
        $this->assertSame('completed', $reconciliation->fresh()->status);
        $this->assertSame(0, $reconciliation->entries()->where('matched', false)->count());
        $this->assertSame(0, DB::table('transaction_lines')->where('matched', false)->count());
    }

    public function test_statement_validation_accepts_negative_balances_and_rejects_invalid_input(): void
    {
        $rules = (new ReconciliationRequest)->rules();
        $this->assertTrue(Validator::make(['balance' => -50, 'date' => '2026-01-01'], $rules)->passes());
        $this->assertTrue(Validator::make(['balance' => 0, 'date' => '2026-01-01'], $rules)->passes());
        $this->assertTrue(Validator::make(['balance' => 'invalid', 'date' => '2099-01-01'], $rules)->fails());
        $this->assertTrue(Validator::make([], $rules)->fails());
        $entryRules = (new ReconciliationEntryRequest)->rules();
        $this->assertTrue(Validator::make(['matched' => false], $entryRules)->passes());
        $this->assertTrue(Validator::make(['matched' => 'invalid'], $entryRules)->fails());
    }

    public function test_failed_adjustment_rolls_back_completion(): void
    {
        DB::table('reconciliations')->insert(['id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 0, 'difference' => 99, 'status' => 'pending']);
        $account = new Account;
        $account->id = 10;
        $reconciliation = Reconciliation::findOrFail(1)->setRelation('account', $account);
        $service = Mockery::mock(ReconciliationService::class)->makePartial();
        $service->shouldReceive('syncTransactions')->once()->andThrow(new \RuntimeException('Failed to synchronize'));
        try {
            $service->saveAdjustment($reconciliation, new ReconciliationParamsData(10, 0, '2026-08-12', 1));
            $this->fail('The synchronization failure should propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Failed to synchronize', $exception->getMessage());
        }
        $this->assertSame('pending', $reconciliation->fresh()->status);
        $this->assertEquals(99, $reconciliation->fresh()->difference);
    }

    public function test_creating_a_completed_reconciliation_returns_to_the_list_with_confirmation(): void
    {
        $user = new User;
        $user->id = 1;
        Auth::setUser($user);
        $account = new Account;
        $account->id = 10;
        $reconciliation = new Reconciliation(['difference' => 0, 'status' => 'completed']);
        $reconciliation->id = 123;
        $pending = new Reconciliation(['difference' => 25, 'status' => 'pending']);
        $pending->id = 124;
        $request = Mockery::mock(ReconciliationRequest::class);
        $request->shouldReceive('validated')->twice()->andReturn(['balance' => 0, 'date' => '2026-01-01']);
        $service = Mockery::mock(ReconciliationService::class);
        $service->shouldReceive('create')->twice()->andReturn($reconciliation, $pending);
        $controller = Mockery::mock(ReconciliationController::class)->makePartial();
        $controller->shouldReceive('authorize')->twice();
        $response = $controller->store($account, $service, $request);
        $this->assertStringEndsWith('/finance/reconciliation', $response->getTargetUrl());
        $this->assertSame(123, session('flash.reconciliation_id'));
        $this->assertNotEmpty(session('flash.banner'));
        $pendingResponse = $controller->store($account, $service, $request);
        $this->assertStringEndsWith('/finance/reconciliation/124', $pendingResponse->getTargetUrl());
    }

    public function test_reopening_completed_reconciliation_clears_line_matches_and_rounds_difference(): void
    {
        DB::table('reconciliations')->insert(['id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 0.3, 'difference' => 0, 'status' => 'completed']);
        DB::table('transactions')->insert(['id' => 1, 'status' => 'verified', 'date' => '2026-08-12']);
        DB::table('transaction_lines')->insert([
            ['id' => 1, 'transaction_id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 0.1, 'type' => 1, 'matched' => true],
            ['id' => 2, 'transaction_id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 0.2, 'type' => 1, 'matched' => true],
        ]);
        foreach ([1, 2] as $lineId) {
            DB::table('reconciliation_entries')->insert(['reconciliation_id' => 1, 'transaction_id' => 1, 'transaction_line_id' => $lineId, 'matched' => true]);
        }
        $account = new Account;
        $account->id = 10;
        $reconciliation = Reconciliation::findOrFail(1)->setRelation('account', $account);
        $service = new ReconciliationService;
        $service->update($reconciliation, new ReconciliationParamsData(10, 0.3, '2026-08-12', 1));
        $this->assertSame('completed', $reconciliation->fresh()->status);
        $service->update($reconciliation, new ReconciliationParamsData(10, 0.31, '2026-08-12', 1));
        $this->assertSame('pending', $reconciliation->fresh()->status);
        $this->assertEquals(-0.01, $reconciliation->fresh()->difference);
        $this->assertSame(0, $reconciliation->entries()->where('matched', true)->count());
        $this->assertSame(0, DB::table('transaction_lines')->where('matched', true)->count());
    }

    public function test_transfer_updates_both_accounts_once(): void
    {
        $account = new Account;
        $account->id = 10;
        $counterAccount = new Account;
        $counterAccount->id = 20;
        $transaction = new Transaction;
        $transaction->setRelation('account', $account)->setRelation('counterAccount', $counterAccount);
        $service = Mockery::mock(ReconciliationService::class);
        $service->shouldReceive('checkOpenReconciliation')->once()->with($account, $transaction);
        $service->shouldReceive('checkOpenReconciliation')->once()->with($counterAccount, $transaction);
        (new UpdateOpenReconciliations($service))->handle(new TransactionCreated($transaction));
        $this->addToAssertionCount(2);
    }

    public function test_new_movements_refresh_all_open_reconciliations(): void
    {
        DB::table('reconciliations')->insert([
            ['id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 0, 'difference' => 99, 'status' => 'pending'],
            ['id' => 2, 'account_id' => 10, 'date' => '2026-08-13', 'amount' => 0, 'difference' => 99, 'status' => 'pending'],
            ['id' => 3, 'account_id' => 10, 'date' => '2026-08-10', 'amount' => 0, 'difference' => 99, 'status' => 'pending'],
        ]);
        $account = new Account;
        $account->id = 10;
        $transaction = new Transaction;
        $transaction->date = '2026-08-11';
        $service = Mockery::mock(ReconciliationService::class)->makePartial();
        $service->shouldReceive('syncTransactions')->once()->with(Mockery::on(fn ($item) => $item->id === 1))->andReturn(new Reconciliation);
        $service->shouldReceive('syncTransactions')->once()->with(Mockery::on(fn ($item) => $item->id === 2))->andReturn(new Reconciliation);
        $service->checkOpenReconciliation($account, $transaction);
        $this->addToAssertionCount(2);
    }

    public function test_completed_entries_cannot_be_unchecked(): void
    {
        $reconciliation = new Reconciliation(['status' => 'completed']);
        $reconciliation->id = 1;
        $entry = new ReconciliationEntry(['reconciliation_id' => 1]);
        $controller = Mockery::mock(ReconciliationController::class)->makePartial();
        $controller->shouldReceive('authorize')->once();
        $service = Mockery::mock(ReconciliationService::class);
        $service->shouldNotReceive('checkLine');
        $this->expectException(HttpException::class);
        $controller->checkReconciliationEntry($reconciliation, $entry, $service, new ReconciliationEntryRequest);
    }

    public function test_delete_releases_entries_without_deleting_transactions(): void
    {
        DB::table('reconciliations')->insert(['id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 0, 'difference' => 0, 'status' => 'completed']);
        DB::table('transactions')->insert(['id' => 1, 'status' => 'verified']);
        DB::table('transaction_lines')->insert(['id' => 1, 'transaction_id' => 1, 'account_id' => 10, 'date' => '2026-08-12', 'amount' => 10, 'type' => 1, 'matched' => true]);
        DB::table('reconciliation_entries')->insert(['reconciliation_id' => 1, 'transaction_id' => 1, 'transaction_line_id' => 1, 'matched' => true]);
        (new ReconciliationService)->delete(Reconciliation::findOrFail(1));
        $this->assertSame(0, DB::table('reconciliation_entries')->count());
        $this->assertSame(0, DB::table('reconciliations')->count());
        $this->assertSame(1, DB::table('transactions')->count());
        $this->assertFalse((bool) DB::table('transaction_lines')->value('matched'));
    }

    public function test_creating_matching_statement_is_completed_and_same_date_is_not_duplicated(): void
    {
        $account = new Account;
        $account->id = 10;
        $account->team_id = 1;
        $service = new ReconciliationService;
        $params = new ReconciliationParamsData(10, 0, '2026-08-12', 1);
        $first = $service->create($account, $params);
        $this->assertSame('completed', $first->status);
        $first->setRelation('account', $account);
        $service = Mockery::mock(ReconciliationService::class)->makePartial();
        $service->shouldReceive('getByDate')->once()->andReturn($first);
        $second = $service->create($account, $params);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Reconciliation::query()->count());
    }

    public function test_failed_entry_creation_does_not_leave_partial_reconciliation(): void
    {
        $account = Mockery::mock(Account::class)->makePartial();
        $account->id = 10;
        $account->team_id = 1;
        $account->shouldReceive('transactionsToReconcile')->once()->andReturn(collect([(object) ['id' => 1, 'transaction_id' => null]]));
        try {
            (new ReconciliationService)->create($account, new ReconciliationParamsData(10, 0, '2026-08-12', 1));
            $this->fail('Creating an invalid entry must fail.');
        } catch (QueryException) {
            $this->assertSame(0, Reconciliation::query()->count());
        }
    }
}
