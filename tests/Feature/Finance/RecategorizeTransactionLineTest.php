<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;
use App\Jobs\RollBudgetForward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecategorizeTransactionLineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    private Category $groceries;

    private Category $personal;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $this->user->ownedTeams()->first()->id;
        $this->user->forceFill(['current_team_id' => $this->teamId])->save();

        $this->groceries = Category::where(['team_id' => $this->teamId, 'display_id' => 'savings_general'])->firstOrFail();
        $this->personal = Category::where(['team_id' => $this->teamId, 'display_id' => 'personal_spending'])->firstOrFail();
    }

    public function test_it_moves_a_non_split_transaction_to_the_new_category(): void
    {
        [$transaction, $line] = $this->seedExpense($this->groceries, 450);

        $this->actingAs($this->user)
            ->patch("/finance/transaction-lines/{$line->id}/category", ['category_id' => $this->personal->id])
            ->assertRedirect();

        $this->assertSame($this->personal->id, (int) $line->fresh()->category_id);
        $this->assertSame($this->personal->id, (int) $transaction->fresh()->category_id);
        $this->assertSame(0, (int) TransactionLine::where(['transaction_id' => $transaction->id, 'anchor' => 0])->value('category_id'));

        $month = '2026-09-01';
        $this->assertEquals(0, (float) BudgetMonth::where(['category_id' => $this->groceries->id, 'month' => $month])->value('activity'));
        $this->assertEquals(-450, (float) BudgetMonth::where(['category_id' => $this->personal->id, 'month' => $month])->value('activity'));
        Queue::assertPushed(RollBudgetForward::class);
    }

    public function test_it_only_moves_the_selected_split_line(): void
    {
        [$transaction, $line] = $this->seedExpense($this->groceries, 300, hasSplits: true);

        $this->actingAs($this->user)
            ->patch("/finance/transaction-lines/{$line->id}/category", ['category_id' => $this->personal->id])
            ->assertRedirect();

        $this->assertSame($this->personal->id, (int) $line->fresh()->category_id);
        $this->assertSame($this->groceries->id, (int) $transaction->fresh()->category_id);
    }

    public function test_it_rejects_a_category_from_another_team(): void
    {
        [, $line] = $this->seedExpense($this->groceries, 100);
        $otherUser = User::factory()->withPersonalTeam()->create();
        $foreignCategory = Category::where('team_id', $otherUser->ownedTeams()->first()->id)->firstOrFail();

        $this->actingAs($this->user)
            ->patch("/finance/transaction-lines/{$line->id}/category", ['category_id' => $foreignCategory->id])
            ->assertSessionHasErrors('category_id');

        $this->assertSame($this->groceries->id, (int) $line->fresh()->category_id);
    }

    public function test_it_requires_a_category(): void
    {
        [, $line] = $this->seedExpense($this->groceries, 100);

        $this->actingAs($this->user)
            ->patch("/finance/transaction-lines/{$line->id}/category", [])
            ->assertSessionHasErrors('category_id');
    }

    public function test_it_forbids_recategorizing_another_teams_line(): void
    {
        [, $line] = $this->seedExpense($this->groceries, 100);
        $intruder = User::factory()->withPersonalTeam()->create();
        $intruder->forceFill(['current_team_id' => $intruder->ownedTeams()->first()->id])->save();

        $this->actingAs($intruder)
            ->patch("/finance/transaction-lines/{$line->id}/category", ['category_id' => $this->personal->id])
            ->assertForbidden();

        $this->assertSame($this->groceries->id, (int) $line->fresh()->category_id);
    }

    public function test_it_requires_authentication(): void
    {
        [, $line] = $this->seedExpense($this->groceries, 100);

        $this->patch("/finance/transaction-lines/{$line->id}/category", ['category_id' => $this->personal->id])
            ->assertRedirect('/login');
    }

    /**
     * @return array{0: Transaction, 1: TransactionLine}
     */
    private function seedExpense(Category $category, float $amount, bool $hasSplits = false): array
    {
        $transaction = new Transaction;
        $transaction->forceFill([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => 1001,
            'category_id' => $category->id,
            'date' => '2026-09-22',
            'currency_code' => 'DOP',
            'description' => 'JUMBO LA ROMANA',
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => $amount,
            'has_splits' => $hasSplits,
            'status' => Transaction::STATUS_VERIFIED,
        ])->save();

        $baseLine = [
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'transaction_id' => $transaction->id,
            'date' => '2026-09-22',
            'amount' => $amount,
            'concept' => 'JUMBO LA ROMANA',
        ];

        $categorizedLine = new TransactionLine;
        $categorizedLine->forceFill([
            ...$baseLine,
            'index' => 0,
            'anchor' => 1,
            'type' => -1,
            'account_id' => 1001,
            'category_id' => $category->id,
            'is_split' => $hasSplits,
        ])->save();

        (new TransactionLine)->forceFill([
            ...$baseLine,
            'index' => 1,
            'anchor' => 0,
            'type' => 1,
            'account_id' => 1002,
            'category_id' => 0,
        ])->save();

        return [$transaction, $categorizedLine];
    }
}
