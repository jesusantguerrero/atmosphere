<?php

namespace App\Console\Commands;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Models\Integration;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Enable the "emails -> tasks" automation for a user: matching Gmail messages
 * become task cards on a board. Mirrors the Universal Bank Parser setup, but
 * the chain is GmailReceived -> CreateTaskFromEmail instead of the bank parser.
 *
 * The default query is `is:starred` — the user stars an email and it shows up
 * as a task, a deterministic, no-flood "inbox to tasks" that needs no AI. Pass
 * --query to widen it (e.g. a label) once the user wants more captured.
 */
class SetupEmailToTasksAutomation extends Command
{
    protected $signature = 'automation:setup-email-tasks
                            {user_id : The ID of the user}
                            {--team_id= : Team ID (defaults to the user\'s current team)}
                            {--integration_id= : Integration ID (defaults to the user\'s Gmail integration)}
                            {--query=is:starred : Gmail search that selects which emails become tasks}
                            {--board=Email : Board name where the task cards land}';

    protected $description = 'Set up the emails-to-tasks automation for a user';

    public function handle(): int
    {
        $this->info('=== Emails → Tasks — Automation Setup ===');
        $this->newLine();

        $user = User::find($this->argument('user_id'));
        if (! $user) {
            $this->error('Error: User not found with ID '.$this->argument('user_id'));

            return Command::FAILURE;
        }
        $this->info("✓ User: {$user->name} (ID: {$user->id})");

        $teamId = $this->option('team_id') ?? $user->current_team_id;
        if (! $teamId) {
            $this->error('Error: No team found. Pass --team_id.');

            return Command::FAILURE;
        }
        $this->info("✓ Team ID: {$teamId}");

        $integrationId = $this->option('integration_id');
        if (! $integrationId) {
            $integration = Integration::where('user_id', $user->id)
                ->whereHas('service', fn ($q) => $q->where('name', 'Gmail'))
                ->first();
            if ($integration) {
                $integrationId = $integration->id;
                $this->info("✓ Gmail integration found (ID: {$integrationId})");
            } else {
                $this->warn('⚠ Gmail integration not found — you may need to link it manually.');
                $integrationId = null;
            }
        }

        $query = (string) $this->option('query');
        $board = (string) $this->option('board');

        $automation = Automation::updateOrCreate(
            [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'name' => 'Email to Tasks',
            ],
            [
                'integration_id' => $integrationId,
                'trigger_id' => 1,
                'description' => 'Turns selected emails (default: starred) into task cards on a board.',
                'sentence' => 'When a matching email is received, create a task',
                'status' => true,
                'is_background' => true,
                'config' => [],
            ]
        );

        $automation->saveTasks([
            [
                'entity' => 'App\\Domains\\Integration\\Actions\\GmailReceived',
                'task_type' => 'trigger',
                'order' => 0,
                'name' => 'Gmail Trigger - Tasks',
                'values' => [
                    'query' => $query,
                ],
            ],
            [
                'entity' => 'App\\Domains\\Integration\\Actions\\CreateTaskFromEmail',
                'task_type' => 'action',
                'order' => 1,
                'name' => 'Create Task From Email',
                'values' => [
                    'board' => $board,
                ],
            ],
        ]);

        $action = $automation->wasRecentlyCreated ? 'Created' : 'Updated';
        $this->newLine();
        $this->info("=== {$action}! ===");
        $this->table(['Property', 'Value'], [
            ['Automation ID', $automation->id],
            ['Name', $automation->name],
            ['Status', $automation->status ? 'Active' : 'Inactive'],
            ['Query', $query],
            ['Board', $board],
            ['Integration', $integrationId ?? '(none — link manually)'],
        ]);

        return Command::SUCCESS;
    }
}
