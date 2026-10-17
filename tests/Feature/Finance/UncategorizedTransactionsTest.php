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

    public function test_uncategorized_filter_excludes_income_transfers_and_deleted_transactions(): void
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
        $this->assertSame([$ids['expense']], array_column($response->json(), 'id'));

        $allDates = $this->getJson('/api/finance/transactions?filter[uncategorized]=1&filter[currency_code]=DOP');
        $allDates->assertOk();
        $this->assertEqualsCanonicalizing([$ids['expense'], $ids['older']], array_column($allDates->json(), 'id'));
        $this->assertEquals(200, array_sum(array_column($allDates->json(), 'total')));

        $this->get('/budgets')->assertInertia(fn ($page) => $page
            ->where('uncategorizedTotals.0.currency_code', 'DOP')
            ->where('uncategorizedTotals.0.count', 2)
            ->where('uncategorizedTotals.0.total', fn ($total) => (float) $total === 200.0)
        );
    }
}
