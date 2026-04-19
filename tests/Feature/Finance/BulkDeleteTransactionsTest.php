<?php

namespace Tests\Feature\Finance;

use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkDeleteTransactionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->user->current_team_id = $this->user->ownedTeams->first()->id;
        $this->user->save();
    }

    public function test_bulk_delete_soft_deletes_only_transactions_in_the_users_team(): void
    {
        $mine = $this->seedTransaction($this->user->current_team_id);
        $alsoMine = $this->seedTransaction($this->user->current_team_id);
        $someoneElses = $this->seedTransaction($this->user->current_team_id + 99);

        $this->actingAs($this->user)
            ->post('/finance/transactions/bulk/delete', [
                'data' => [$mine->id, $alsoMine->id, $someoneElses->id],
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('transactions', ['id' => $mine->id]);
        $this->assertSoftDeleted('transactions', ['id' => $alsoMine->id]);
        $this->assertDatabaseHas('transactions', ['id' => $someoneElses->id, 'deleted_at' => null]);
    }

    public function test_bulk_delete_rejects_empty_payload(): void
    {
        $this->actingAs($this->user)
            ->post('/finance/transactions/bulk/delete', ['data' => []])
            ->assertSessionHasErrors('data');
    }

    public function test_bulk_delete_requires_authentication(): void
    {
        $this->post('/finance/transactions/bulk/delete', ['data' => [1]])
            ->assertRedirect('/login');
    }

    private function seedTransaction(int $teamId): Transaction
    {
        $transaction = new Transaction;
        $transaction->forceFill([
            'team_id' => $teamId,
            'user_id' => $this->user->id,
            'account_id' => 1001,
            'date' => '2026-04-17',
            'currency_code' => 'DOP',
            'description' => 'to delete',
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => 100.00,
            'status' => Transaction::STATUS_DRAFT,
        ])->save();

        return $transaction;
    }
}
