<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Backs the dashboard "Remove" / "Approve" draft buttons. The frontend was
 * posting to /transactions/remove-all-drafts and /transactions/approve-all-drafts;
 * neither route existed (silent 404) so nothing happened. These tests lock
 * down the new endpoints.
 */
class DraftBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeDraft(int $teamId, int $userId, int $categoryId): Transaction
    {
        return Transaction::forceCreate([
            'team_id' => $teamId,
            'user_id' => $userId,
            'category_id' => $categoryId,
            'status' => Transaction::STATUS_DRAFT,
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => now()->format('Y-m-d'),
            'total' => 100,
            'description' => 'pdf import',
            'currency_code' => 'DOP',
        ]);
    }

    private function makeVerified(int $teamId, int $userId, int $categoryId): Transaction
    {
        return Transaction::forceCreate([
            'team_id' => $teamId,
            'user_id' => $userId,
            'category_id' => $categoryId,
            'status' => Transaction::STATUS_VERIFIED,
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => now()->format('Y-m-d'),
            'total' => 100,
            'description' => 'verified',
            'currency_code' => 'DOP',
        ]);
    }

    public function test_remove_all_drafts_deletes_only_drafts_for_current_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $stranger = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $strangerTeam = $stranger->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();
        $strangerCategory = Category::where('team_id', $strangerTeam->id)->whereNotNull('parent_id')->first();

        $draftA = $this->makeDraft($team->id, $user->id, $category->id);
        $draftB = $this->makeDraft($team->id, $user->id, $category->id);
        $verified = $this->makeVerified($team->id, $user->id, $category->id);
        $strangerDraft = $this->makeDraft($strangerTeam->id, $stranger->id, $strangerCategory->id);

        $this->actingAs($user)
            ->post('/transactions/remove-all-drafts')
            ->assertRedirect();

        $this->assertNull(Transaction::find($draftA->id), 'draft A should be deleted');
        $this->assertNull(Transaction::find($draftB->id), 'draft B should be deleted');
        $this->assertNotNull(Transaction::find($verified->id), 'verified must survive');
        $this->assertNotNull(Transaction::find($strangerDraft->id), 'other team draft must not be touched');
    }

    public function test_approve_all_drafts_promotes_only_drafts_for_current_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $stranger = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $strangerTeam = $stranger->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();
        $strangerCategory = Category::where('team_id', $strangerTeam->id)->whereNotNull('parent_id')->first();

        $draft = $this->makeDraft($team->id, $user->id, $category->id);
        $verifiedAlready = $this->makeVerified($team->id, $user->id, $category->id);
        $strangerDraft = $this->makeDraft($strangerTeam->id, $stranger->id, $strangerCategory->id);

        $this->actingAs($user)
            ->post('/transactions/approve-all-drafts')
            ->assertRedirect();

        $this->assertSame(Transaction::STATUS_VERIFIED, Transaction::find($draft->id)->status);
        $this->assertSame(Transaction::STATUS_VERIFIED, Transaction::find($verifiedAlready->id)->status);
        $this->assertSame(Transaction::STATUS_DRAFT, Transaction::find($strangerDraft->id)->status, 'other team draft must keep status');
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->post('/transactions/remove-all-drafts')->assertRedirect('/login');
        $this->post('/transactions/approve-all-drafts')->assertRedirect('/login');
    }
}
