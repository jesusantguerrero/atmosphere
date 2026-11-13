<?php

namespace Tests\Feature\Finance;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

class UncategorizedTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_uncategorized_filter_includes_income_and_excludes_transfers_and_deleted_transactions(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();
        (new AccountDetailTypesCreate)->create();
        $counterAccount = Account::create([
            'name' => 'Internal account', 'team_id' => $user->current_team_id, 'user_id' => $user->id,
            'currency_code' => 'DOP', 'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::BANK)->value('id'),
        ]);
        $ids = [];
        foreach (['expense', 'income', 'transfer', 'deleted', 'older', 'internal', 'foreign', 'categorized', 'usd'] as $kind) {
            $transaction = new Transaction;
            $transaction->forceFill([
                'team_id' => $kind === 'foreign' ? $user->current_team_id + 999 : $user->current_team_id,
                'user_id' => $user->id,
                'account_id' => 1001,
                'date' => $kind === 'older' ? '2025-01-15' : '2026-10-17',
                'currency_code' => $kind === 'usd' ? 'USD' : 'DOP',
                'counter_account_id' => $kind === 'internal' ? $counterAccount->id : null,
                'category_id' => $kind === 'categorized' ? 9999 : null,
                'description' => $kind,
                'direction' => $kind === 'income' ? Transaction::DIRECTION_DEBIT : Transaction::DIRECTION_CREDIT,
                'is_transfer' => $kind === 'transfer',
                'total' => 100,
                'status' => Transaction::STATUS_VERIFIED,
            ])->save();
            $ids[$kind] = $transaction->id;
            if ($kind === 'deleted') {
                $transaction->delete();
            }
        }

        $response = $this->actingAs($user)->getJson('/api/finance/transactions?'.http_build_query([
            'filter' => ['uncategorized' => 1, 'date' => '2026-10-01~2026-10-31', 'currency_code' => 'DOP'],
        ]));
        $response->assertOk();
        $this->assertEqualsCanonicalizing([$ids['expense'], $ids['income']], array_column($response->json(), 'id'));

        $allDates = $this->getJson('/api/finance/transactions?filter[uncategorized]=1&filter[currency_code]=DOP');
        $allDates->assertOk();
        $this->assertEqualsCanonicalizing([$ids['expense'], $ids['older'], $ids['income']], array_column($allDates->json(), 'id'));

        // Income nets against expenses: the total is what the budget is off by.
        $this->get('/budgets')->assertInertia(fn ($page) => $page
            ->where('uncategorizedTotals.0.currency_code', 'DOP')
            ->where('uncategorizedTotals.0.count', 3)
            ->where('uncategorizedTotals.0.total', fn ($total) => (float) $total === 100.0)
        );
    }

    public function test_expenses_saved_with_category_zero_count_as_uncategorized(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();
        $transaction = new Transaction;
        $transaction->forceFill([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => 1001,
            'date' => '2026-03-10',
            'currency_code' => 'DOP',
            'category_id' => 0,
            'description' => 'Ret. VISA',
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => 7700,
            'status' => Transaction::STATUS_VERIFIED,
        ])->save();

        $response = $this->actingAs($user)->getJson('/api/finance/transactions?filter[uncategorized]=1&filter[currency_code]=DOP');
        $response->assertOk();
        $this->assertSame([$transaction->id], array_column($response->json(), 'id'));

        $this->get('/budgets')->assertInertia(fn ($page) => $page
            ->where('uncategorizedTotals.0.count', 1)
            ->where('uncategorizedTotals.0.total', fn ($total) => (float) $total === 7700.0)
        );
    }

    public function test_income_without_a_category_is_listed_until_it_goes_to_ready_to_assign(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();
        $income = new Transaction;
        $income->forceFill([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => 1001,
            'date' => '2026-04-01',
            'currency_code' => 'DOP',
            'category_id' => null,
            'description' => 'TRANSFERENCIA RECIBIDA DE RODRIGUEZ',
            'direction' => Transaction::DIRECTION_DEBIT,
            'total' => 50,
            'status' => Transaction::STATUS_VERIFIED,
        ])->save();

        $this->actingAs($user)->getJson('/api/finance/transactions?filter[uncategorized]=1&filter[currency_code]=DOP')
            ->assertOk()
            ->assertJsonPath('0.id', $income->id);
        $this->get('/budgets')->assertInertia(fn ($page) => $page
            ->where('uncategorizedTotals.0.count', 1)
            ->where('uncategorizedTotals.0.total', fn ($total) => (float) $total === -50.0)
        );

        $income->forceFill(['category_id' => 9999])->save();

        $this->getJson('/api/finance/transactions?filter[uncategorized]=1&filter[currency_code]=DOP')
            ->assertOk()
            ->assertJsonCount(0);
    }
}
