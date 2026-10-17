<?php

namespace Tests\Feature\Finance;

use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UncategorizedTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_uncategorized_filter_excludes_income_transfers_and_deleted_transactions(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();
        $ids = [];
        foreach (['expense', 'income', 'transfer', 'deleted'] as $kind) {
            $transaction = new Transaction;
            $transaction->forceFill([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id,
                'account_id' => 1001,
                'date' => '2026-10-17',
                'currency_code' => 'DOP',
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
            'filter' => ['uncategorized' => 1, 'date' => '2026-10-01~2026-10-31'],
        ]));
        $response->assertOk();
        $this->assertSame([$ids['expense']], array_column($response->json(), 'id'));
    }
}
