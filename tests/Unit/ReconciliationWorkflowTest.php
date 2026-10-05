<?php

namespace Tests\Unit;

use App\Domains\Transaction\Data\ReconciliationParamsData;
use App\Domains\Transaction\Http\Controllers\ReconciliationController;
use App\Domains\Transaction\Services\ReconciliationService;
use App\Http\Requests\ReconciliationEntryRequest;
use App\Http\Requests\ReconciliationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Core\Account;
use Mockery;

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

    public function test_creating_an_already_balanced_reconciliation_still_redirects_to_detail(): void
    {
        $user = new User;
        $user->id = 1;
        Auth::setUser($user);
        $account = new Account;
        $account->id = 10;
        $reconciliation = new Reconciliation(['difference' => 0]);
        $reconciliation->id = 123;
        $request = Mockery::mock(ReconciliationRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['balance' => 0, 'date' => '2026-01-01']);
        $service = Mockery::mock(ReconciliationService::class);
        $service->shouldReceive('create')->once()->andReturn($reconciliation);
        $controller = Mockery::mock(ReconciliationController::class)->makePartial();
        $controller->shouldReceive('authorize')->once();
        $response = $controller->store($account, $service, $request);
        $this->assertStringEndsWith('/finance/reconciliation/123', $response->getTargetUrl());
    }
}
