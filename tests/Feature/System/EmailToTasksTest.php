<?php

namespace Tests\Feature\System;

use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationService;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Integration\Actions\CreateTaskFromEmail;
use App\Domains\Integration\Actions\GmailReceived;
use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Services\EmailToTasksAutomation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Tests\TestCase;

/**
 * Covers the "star an email -> get a task card" toggle end to end (minus the
 * Gmail call itself): enabling it must persist a runnable background automation
 * and its action must actually land a PlanItem on the board.
 *
 * Both halves used to fail silently — `is_background` / `order` / `plan_id`
 * were missing from the models' fillable lists, so mass assignment threw
 * locally and dropped the columns in production, leaving a toggle that looked
 * on while nothing ever reached the board.
 */
class EmailToTasksTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->fresh()->ownedTeams()->latest('id')->first()->id;
        $user->save();

        return $user;
    }

    private function connectGoogle(User $user): Integration
    {
        $service = AutomationService::create([
            'name' => 'Google',
            'label' => 'Google',
            'type' => 'external',
            'entity' => 'gmail',
            'description' => 'Read emails',
            'logo' => '',
        ]);

        return Integration::create([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'automation_service_id' => $service->id,
            'name' => 'Gmail',
            'hash' => $user->email,
            'token' => json_encode(['access_token' => 'fake-access-token']),
        ]);
    }

    /** @return array{0: Automation, 1: AutomationTaskAction} the automation and its action task */
    private function enable(User $user): array
    {
        $automation = EmailToTasksAutomation::enable($user);
        $action = $automation->tasks()->where('task_type', 'action')->firstOrFail();

        return [$automation, $action];
    }

    private function payload(string $messageId, string $subject = 'Pay the school fee'): array
    {
        return [
            'from' => 'School <billing@school.edu>',
            'subject' => $subject,
            'messageId' => $messageId,
            'date' => 'Sat, 15 Aug 2026 09:12:00 -0400',
            'message' => '<p>Reminder: the fee is due on Monday.</p>',
        ];
    }

    public function test_enabling_persists_a_runnable_background_automation(): void
    {
        $user = $this->makeUser();
        $integration = $this->connectGoogle($user);

        [$automation] = $this->enable($user);

        $this->assertTrue($automation->status);
        $this->assertTrue($automation->is_background, 'Automation must be flagged background or app:automation-check skips it.');
        $this->assertSame($integration->id, $automation->integration_id);

        $tasks = $automation->tasks()->get();
        $this->assertCount(2, $tasks);
        $this->assertSame(GmailReceived::class, $tasks[0]->entity);
        $this->assertSame(0, (int) $tasks[0]->order);
        $this->assertSame(CreateTaskFromEmail::class, $tasks[1]->entity);
        $this->assertSame(1, (int) $tasks[1]->order, 'Task order must persist or the runner can execute the action before the trigger.');
        $this->assertSame('is:starred', json_decode($tasks[0]->values, true)['query']);
    }

    public function test_the_scheduler_only_picks_up_enabled_background_automations(): void
    {
        $user = $this->makeUser();
        $this->connectGoogle($user);

        [$automation] = $this->enable($user);

        $runnable = fn () => Automation::where('is_background', true)->where('status', true)->pluck('id')->all();

        $this->assertContains($automation->id, $runnable());

        EmailToTasksAutomation::disable($user);

        $this->assertNotContains($automation->id, $runnable(), 'A disabled automation must stop running.');
    }

    public function test_status_reflects_the_toggle(): void
    {
        $user = $this->makeUser();
        $this->connectGoogle($user);

        $this->assertFalse(EmailToTasksAutomation::status($user)['enabled']);

        $this->enable($user);
        $status = EmailToTasksAutomation::status($user);

        $this->assertTrue($status['enabled']);
        $this->assertTrue($status['connected']);
        $this->assertSame('is:starred', $status['query']);
    }

    public function test_a_starred_email_lands_as_a_task_card_on_the_board(): void
    {
        $user = $this->makeUser();
        $this->connectGoogle($user);
        [$automation, $action] = $this->enable($user);

        CreateTaskFromEmail::handle($automation, $this->payload('<abc-123@mail.gmail.com>'), $action, $action, $action);

        $board = Plan::where(['team_id' => $user->current_team_id, 'name' => 'Email'])->first();
        $this->assertNotNull($board, 'The Email board should be created on first capture.');

        $item = PlanItem::where('resource_id', '<abc-123@mail.gmail.com>')->first();
        $this->assertNotNull($item, 'The starred email never became a task card.');
        $this->assertSame('Pay the school fee', $item->title);
        $this->assertSame($board->id, $item->plan_id, 'A card with the wrong plan_id is orphaned from its board.');
        $this->assertSame($board->stages->first()->id, $item->stage_id);
        $this->assertSame('gmail', $item->resource_type);

        $fields = $item->fields->pluck('value', 'field_name');
        $this->assertSame('School <billing@school.edu>', $fields['from']);
        $this->assertStringContainsString('the fee is due on Monday', $fields['snippet']);
    }

    public function test_resyncing_the_same_email_updates_instead_of_duplicating(): void
    {
        $user = $this->makeUser();
        $this->connectGoogle($user);
        [$automation, $action] = $this->enable($user);

        CreateTaskFromEmail::handle($automation, $this->payload('<dup-1@mail.gmail.com>'), $action, $action, $action);
        CreateTaskFromEmail::handle($automation, $this->payload('<dup-1@mail.gmail.com>', 'Pay the school fee (updated)'), $action, $action, $action);

        $items = PlanItem::where('resource_id', '<dup-1@mail.gmail.com>')->get();
        $this->assertCount(1, $items);
        $this->assertSame('Pay the school fee (updated)', $items[0]->title);
        $this->assertSame(
            1,
            $items[0]->fields->where('field_name', 'subject')->count(),
            'Re-syncing must update the existing field value, not append another.'
        );
    }

    public function test_an_email_without_a_message_id_is_skipped(): void
    {
        $user = $this->makeUser();
        $this->connectGoogle($user);
        [$automation, $action] = $this->enable($user);

        $payload = $this->payload('');
        unset($payload['messageId']);

        CreateTaskFromEmail::handle($automation, $payload, $action, $action, $action);

        $this->assertSame(0, PlanItem::count());
    }
}
