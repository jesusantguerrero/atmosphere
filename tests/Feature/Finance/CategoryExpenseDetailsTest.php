<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CategoryExpenseDetailsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $this->user->ownedTeams()->first()->id;
        $this->user->forceFill(['current_team_id' => $this->teamId])->save();
    }

    public function test_group_details_list_the_lines_of_every_child_category(): void
    {
        $group = Category::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'name' => 'Diana',
            'display_id' => 'diana',
        ]);
        $tuition = $this->childOf($group, 'Colegio Mensualidad');
        $supplies = $this->childOf($group, 'Colegio Utiles');
        $outsider = Category::where(['team_id' => $this->teamId, 'display_id' => 'personal_spending'])->firstOrFail();

        $this->seedExpense($tuition, 11500);
        $this->seedExpense($supplies, 8400);
        $this->seedExpense($outsider, 999);

        $response = $this->actingAs($this->user)
            ->getJson("/api/category-transactions/{$group->id}/details?filter[date]=2026-09-01~2026-09-30")
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            [$tuition->id, $supplies->id],
            collect($response->json('transactions.data'))->pluck('category_id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertEquals(-19900, (float) $response->json('transactions.total'));
    }

    public function test_child_details_only_list_that_category(): void
    {
        $group = Category::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'name' => 'Diana',
            'display_id' => 'diana',
        ]);
        $tuition = $this->childOf($group, 'Colegio Mensualidad');
        $supplies = $this->childOf($group, 'Colegio Utiles');

        $this->seedExpense($tuition, 11500);
        $this->seedExpense($supplies, 8400);

        $response = $this->actingAs($this->user)
            ->getJson("/api/category-transactions/{$tuition->id}/details?filter[date]=2026-09-01~2026-09-30")
            ->assertOk();

        $this->assertSame(
            [$tuition->id],
            collect($response->json('transactions.data'))->pluck('category_id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_details_of_another_teams_category_are_forbidden(): void
    {
        $foreignCategory = $this->foreignCategory();

        $this->actingAs($this->user)
            ->getJson("/api/category-transactions/{$foreignCategory->id}/details?filter[date]=2026-09-01~2026-09-30")
            ->assertForbidden();
    }

    public function test_monthly_totals_of_another_teams_category_are_forbidden(): void
    {
        $foreignCategory = $this->foreignCategory();

        $this->actingAs($this->user)
            ->getJson("/api/category-transactions/{$foreignCategory->id}?filter[date]=2026-09-01~2026-09-30")
            ->assertForbidden();
    }

    private function foreignCategory(): Category
    {
        $otherUser = User::factory()->withPersonalTeam()->create();

        return Category::where('team_id', $otherUser->ownedTeams()->first()->id)->firstOrFail();
    }

    private function childOf(Category $group, string $name): Category
    {
        return Category::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'parent_id' => $group->id,
            'name' => $name,
            'display_id' => str($name)->slug('_')->toString(),
        ]);
    }

    private function seedExpense(Category $category, float $amount): void
    {
        $transaction = new Transaction;
        $transaction->forceFill([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => 1001,
            'category_id' => $category->id,
            'date' => '2026-09-05',
            'currency_code' => 'DOP',
            'description' => $category->name,
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => $amount,
            'status' => Transaction::STATUS_VERIFIED,
        ])->save();

        (new TransactionLine)->forceFill([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'transaction_id' => $transaction->id,
            'date' => '2026-09-05',
            'amount' => $amount,
            'concept' => $category->name,
            'index' => 0,
            'anchor' => 1,
            'type' => -1,
            'account_id' => 1001,
            'category_id' => $category->id,
        ])->save();
    }
}
