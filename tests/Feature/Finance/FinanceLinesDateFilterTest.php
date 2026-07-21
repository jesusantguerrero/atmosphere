<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Regression coverage for the finance/lines listing date filter.
 *
 * Links such as `/finance/lines?filter[category_id]=X&filter[date]=2026-05-01~2026-05-31`
 * must restrict the listing to the requested period. The controller previously gated the
 * date range on `filter[dates]` (plural) while the URL sends `filter[date]` (singular),
 * so the period was silently dropped and every transaction in the category was returned.
 */
class FinanceLinesDateFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $this->user->ownedTeams()->first()->id;
        $this->user->forceFill(['current_team_id' => $this->teamId])->save();
    }

    public function test_listing_is_restricted_to_the_requested_date_range(): void
    {
        $category = Category::where('team_id', $this->teamId)
            ->whereNotNull('parent_id')
            ->firstOrFail();

        $inRange = $this->seedVerifiedTransaction($category->id, '2026-05-15');
        $outOfRange = $this->seedVerifiedTransaction($category->id, '2026-04-15');

        $this->actingAs($this->user)
            ->get("/finance/lines?filter[category_id]={$category->id}&filter[date]=2026-05-01~2026-05-31")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('transactions', 1)
                ->where('transactions.0.id', $inRange)
            );

        $this->assertNotSame($inRange, $outOfRange);
    }

    public function test_listing_returns_all_dates_when_no_range_is_provided(): void
    {
        $category = Category::where('team_id', $this->teamId)
            ->whereNotNull('parent_id')
            ->firstOrFail();

        $this->seedVerifiedTransaction($category->id, '2026-05-15');
        $this->seedVerifiedTransaction($category->id, '2026-04-15');

        $this->actingAs($this->user)
            ->get("/finance/lines?filter[category_id]={$category->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('transactions', 2));
    }

    private function seedVerifiedTransaction(int $categoryId, string $date): int
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => 1001,
            'date' => $date,
            'description' => 'Expense '.$date,
            'direction' => 'WITHDRAW',
            'total' => 100.00,
            'currency_code' => 'DOP',
            'status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transaction_lines')->insert([
            'transaction_id' => $transactionId,
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => 1001,
            'category_id' => $categoryId,
            'date' => $date,
            'type' => -1,
            'amount' => 100.00,
            'anchor' => 1,
            'index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $transactionId;
    }
}
