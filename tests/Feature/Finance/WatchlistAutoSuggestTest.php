<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Watchlist\Models\Watchlist;
use Modules\Watchlist\Services\WatchlistAutoSuggestService;
use Tests\TestCase;

/**
 * WL-8: surface top payees the user spent on this month but isn't tracking via any
 * payee-type watchlist yet. Powers the weekly suggestion notification.
 */
class WatchlistAutoSuggestTest extends TestCase
{
    use RefreshDatabase;

    private function createPayee(int $teamId, int $userId, string $name): int
    {
        return DB::table('payees')->insertGetId([
            'team_id' => $teamId,
            'user_id' => $userId,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function recordExpense(int $teamId, int $userId, int $payeeId, int $categoryId, float $amount, ?string $date = null): void
    {
        Transaction::forceCreate([
            'team_id' => $teamId,
            'user_id' => $userId,
            'category_id' => $categoryId,
            'payee_id' => $payeeId,
            'status' => 'verified',
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => $date ?? now()->startOfMonth()->addDays(5)->format('Y-m-d'),
            'total' => $amount,
            'description' => 'tx',
            'currency_code' => 'DOP',
        ]);
    }

    public function test_surfaces_top_payees_by_spend(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        $heavy = $this->createPayee($team->id, $user->id, 'Uber Eats');
        $medium = $this->createPayee($team->id, $user->id, 'Caribbean Cinema');
        $light = $this->createPayee($team->id, $user->id, 'Local Coffee');

        $this->recordExpense($team->id, $user->id, $heavy, $category->id, 5000);
        $this->recordExpense($team->id, $user->id, $medium, $category->id, 1500);
        $this->recordExpense($team->id, $user->id, $light, $category->id, 200);

        $suggestions = (new WatchlistAutoSuggestService)->getTopUntrackedPayees($team->id, null, 3);

        $this->assertCount(3, $suggestions);
        $this->assertSame('Uber Eats', $suggestions[0]['payee_name']);
        $this->assertSame(5000.0, $suggestions[0]['total']);
        $this->assertSame('Caribbean Cinema', $suggestions[1]['payee_name']);
    }

    public function test_excludes_payees_already_in_a_payee_watchlist(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        $tracked = $this->createPayee($team->id, $user->id, 'Tracked already');
        $untracked = $this->createPayee($team->id, $user->id, 'Surface me');

        Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Existing',
            'type' => Watchlist::TYPE_PAYEE,
            'input' => [$tracked],
        ]);

        $this->recordExpense($team->id, $user->id, $tracked, $category->id, 9999);
        $this->recordExpense($team->id, $user->id, $untracked, $category->id, 100);

        $suggestions = (new WatchlistAutoSuggestService)->getTopUntrackedPayees($team->id);

        $names = array_column($suggestions, 'payee_name');
        $this->assertContains('Surface me', $names);
        $this->assertNotContains('Tracked already', $names);
    }

    public function test_respects_limit(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        for ($i = 0; $i < 7; $i++) {
            $payeeId = $this->createPayee($team->id, $user->id, "Payee {$i}");
            $this->recordExpense($team->id, $user->id, $payeeId, $category->id, 100 * ($i + 1));
        }

        $suggestions = (new WatchlistAutoSuggestService)->getTopUntrackedPayees($team->id, null, 3);

        $this->assertCount(3, $suggestions);
    }
}
