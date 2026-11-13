<?php

namespace Tests\Feature\Today;

use App\Domains\AppCore\Models\Planner;
use App\Models\User;
use App\Notifications\WeeklyDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WeeklyDigestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-15 19:00:00');
        Notification::fake();
    }

    private function owner(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();

        return $user;
    }

    private function plan(User $user, string $date, string $name, ?float $total = null, string $status = Planner::STATUS_PLANNED): void
    {
        Planner::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => $name,
            'date' => $date,
            'total' => $total,
            'notes' => 'Llevar merienda',
            'source' => 'school',
            'status' => $status,
        ]);
    }

    public function test_the_owner_gets_the_coming_weeks_school_events(): void
    {
        $user = $this->owner();
        $this->plan($user, '2026-11-18', 'Excursión al planetario', 500);
        $this->plan($user, '2026-11-25', 'Fuera de la semana');
        $this->plan($user, '2026-11-19', 'Ya hecho', status: Planner::STATUS_COMPLETED);

        $this->artisan('loger:weekly-digest')->assertSuccessful();

        Notification::assertSentTo($user, WeeklyDigest::class, function (WeeklyDigest $notification) use ($user) {
            $data = $notification->toArray($user);

            return count($data['events']) === 1
                && $data['events'][0]['name'] === 'Excursión al planetario'
                && str_contains($data['message'], '18/11 Excursión al planetario (RD$500.00) — Llevar merienda');
        });
    }

    public function test_an_empty_week_sends_nothing(): void
    {
        $user = $this->owner();
        $this->plan($user, '2026-12-20', 'Navidad en el colegio');

        $this->artisan('loger:weekly-digest')->assertSuccessful();

        Notification::assertNothingSentTo($user);
    }
}
