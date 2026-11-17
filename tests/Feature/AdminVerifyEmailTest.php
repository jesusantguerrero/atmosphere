<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\FeatureFlagSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminVerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureFlagSeeder::class);
    }

    private function admin(string $role): User
    {
        return User::factory()->withPersonalTeam()->create(['role' => $role]);
    }

    public function test_a_super_admin_marks_an_unverified_email_as_verified(): void
    {
        Event::fake([Verified::class]);
        $target = User::factory()->unverified()->create();

        $this->actingAs($this->admin('super_admin'))
            ->patch(route('admin.users.verify-email', $target))
            ->assertRedirect();

        $this->assertTrue($target->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class, fn (Verified $event) => $event->user->is($target));
    }

    public function test_verifying_an_already_verified_email_keeps_its_date(): void
    {
        Event::fake([Verified::class]);
        $target = User::factory()->create(['email_verified_at' => now()->subYear()]);
        $verifiedAt = $target->email_verified_at->toDateTimeString();

        $this->actingAs($this->admin('super_admin'))
            ->patch(route('admin.users.verify-email', $target))
            ->assertRedirect();

        $this->assertSame($verifiedAt, $target->fresh()->email_verified_at->toDateTimeString());
        Event::assertNotDispatched(Verified::class);
    }

    public function test_only_a_super_admin_can_verify_an_email(): void
    {
        $target = User::factory()->unverified()->create();

        $this->actingAs($this->admin('admin'))
            ->patch(route('admin.users.verify-email', $target))
            ->assertForbidden();
        $this->actingAs($this->admin('user'))
            ->patch(route('admin.users.verify-email', $target))
            ->assertForbidden();

        $this->assertFalse($target->fresh()->hasVerifiedEmail());
    }

    public function test_the_detail_page_offers_the_action_only_to_a_super_admin_on_unverified_users(): void
    {
        $unverified = User::factory()->unverified()->create();
        $verified = User::factory()->create();

        $this->actingAs($this->admin('super_admin'))->get(route('admin.users.show', $unverified))
            ->assertInertia(fn ($page) => $page->where('targetUser.can_verify_email', true));
        $this->actingAs($this->admin('super_admin'))->get(route('admin.users.show', $verified))
            ->assertInertia(fn ($page) => $page->where('targetUser.can_verify_email', false));
        $this->actingAs($this->admin('admin'))->get(route('admin.users.show', $unverified))
            ->assertInertia(fn ($page) => $page->where('targetUser.can_verify_email', false));
    }
}
