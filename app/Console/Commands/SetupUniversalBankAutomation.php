<?php

namespace App\Console\Commands;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Actions\UniversalBankParser;
use App\Domains\Integration\Models\Integration;
use App\Models\Account;
use App\Models\User;
use Illuminate\Console\Command;

class SetupUniversalBankAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:setup-universal-bank
                            {user_id : The ID of the user}
                            {--team_id= : The team ID (optional, uses user\'s current team if not provided)}
                            {--account_id= : The account ID (optional, uses user\'s first account if not provided)}
                            {--integration_id= : The integration ID (optional, finds Gmail integration if not provided)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up Universal Bank Parser automation for a user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('=== Universal Bank Parser - Automation Setup ===');
        $this->newLine();

        // Step 1: Get user
        $this->info('Step 1: Finding user...');
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (! $user) {
            $this->error("Error: User not found with ID {$userId}");

            return Command::FAILURE;
        }
        $this->info("✓ User found: {$user->name} (ID: {$user->id})");
        $this->newLine();

        // Step 2: Get team
        $this->info('Step 2: Determining team...');
        $teamId = $this->option('team_id') ?? $user->current_team_id;

        if (! $teamId) {
            $this->error('Error: No team found. Please provide --team_id option');

            return Command::FAILURE;
        }
        $this->info("✓ Team ID: {$teamId}");
        $this->newLine();

        // Step 3: Get integration
        $this->info('Step 3: Finding Gmail integration...');
        $integrationId = $this->option('integration_id');

        if (! $integrationId) {
            $integration = Integration::where('user_id', $user->id)
                ->whereHas('service', function ($query) {
                    $query->where('name', 'Gmail');
                })
                ->first();

            if ($integration) {
                $integrationId = $integration->id;
                $this->info("✓ Gmail integration found (ID: {$integrationId})");
            } else {
                $this->warn('⚠ Gmail integration not found. Creating without integration.');
                $this->warn('You may need to link this automation to your Gmail integration manually.');
                $integrationId = null;
            }
        } else {
            $this->info("✓ Using provided integration ID: {$integrationId}");
        }
        $this->newLine();

        // Step 4: Get account
        $this->info('Step 4: Finding account...');
        $accountId = $this->option('account_id');

        if (! $accountId) {
            $account = Account::where('user_id', $user->id)->first();

            if (! $account) {
                $this->error('Error: No account found for this user. Please provide --account_id option');

                return Command::FAILURE;
            }
            $accountId = $account->id;
            $this->info("✓ Account found: {$account->name} (ID: {$accountId})");
        } else {
            $account = Account::find($accountId);
            if (! $account) {
                $this->error("Error: Account not found with ID {$accountId}");

                return Command::FAILURE;
            }
            $this->info("✓ Using account: {$account->name} (ID: {$accountId})");
        }
        $this->newLine();

        // Step 5: Find or create automation
        $this->info('Step 5: Setting up Universal Bank Parser automation...');

        $automation = Automation::updateOrCreate(
            [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'name' => 'Universal Bank Transaction Parser',
            ],
            [
                'integration_id' => $integrationId,
                'trigger_id' => 1,
                'description' => 'Processes transaction emails from BHD, APAP, BSC, and other banks in a single automation',
                'sentence' => 'When email received from banks, parse and create transaction',
                'status' => true,
                'is_background' => true,
                'config' => [
                    'bank_patterns' => UniversalBankParser::getAllBankPatterns(),
                ],
            ]
        );

        if ($automation->wasRecentlyCreated) {
            $this->info("✓ Automation created: {$automation->name} (ID: {$automation->id})");
        } else {
            $this->info("✓ Automation updated: {$automation->name} (ID: {$automation->id})");
            $this->warn('  Existing automation found - configuration and tasks have been updated');
        }
        $this->newLine();

        // Step 6: Add tasks to automation
        $this->info('Step 6: Adding tasks to automation...');

        // Build Gmail query from all bank email addresses
        $gmailQuery = UniversalBankParser::buildGmailQuery();

        $tasks = [
            [
                'entity' => 'App\\Domains\\Integration\\Actions\\GmailReceived',
                'task_type' => 'trigger',
                'order' => 0,
                'name' => 'Gmail Trigger - All Banks',
                'values' => [
                    'query' => $gmailQuery,
                ],
            ],
            [
                'entity' => 'App\\Domains\\Integration\\Actions\\UniversalBankParser',
                'task_type' => 'component',
                'order' => 1,
                'name' => 'Parse Bank Email',
                'values' => [],
            ],
            [
                'entity' => 'App\\Domains\\Integration\\Actions\\TransactionCreateEntry',
                'task_type' => 'action',
                'order' => 2,
                'name' => 'Create Transaction',
                'values' => [
                    'account_id' => $accountId,
                    'date' => '${date}',
                    'currency_code' => '${currencyCode}',
                    'category_id' => '',
                    'description' => '${description}',
                    'direction' => 'WITHDRAW',
                    'total' => '${amount}',
                    'items' => '',
                    'payee' => '${payee}',
                    'metaData' => '',
                ],
            ],
        ];

        $automation->saveTasks($tasks);

        $this->info('✓ Tasks added:');
        $this->line('  1. Gmail Trigger (fetches emails from all banks)');
        $this->line('  2. Universal Bank Parser (detects bank and parses)');
        $this->line('  3. Transaction Create Entry (saves transaction)');
        $this->newLine();

        // Step 7: Summary
        $action = $automation->wasRecentlyCreated ? 'Created' : 'Updated';
        $this->info("=== Setup Complete! ({$action}) ===");
        $this->newLine();

        $this->table(
            ['Property', 'Value'],
            [
                ['Automation ID', $automation->id],
                ['Name', $automation->name],
                ['Status', $automation->status ? 'Active' : 'Inactive'],
                ['User', $user->name.' (ID: '.$user->id.')'],
                ['Team ID', $teamId],
                ['Account', $account->name.' (ID: '.$accountId.')'],
                ['Integration ID', $integrationId ?? 'Not set'],
                ['Tasks', count($tasks)],
            ]
        );

        $this->newLine();
        $this->info('Gmail Query:');
        $this->line('  '.$gmailQuery);
        $this->newLine();

        $this->info('Supported Banks:');
        $bankPatterns = UniversalBankParser::getAllBankPatterns();
        foreach ($bankPatterns as $bankCode => $patterns) {
            $emails = implode(', ', $patterns['email_addresses']);
            $this->line("  ✓ {$bankCode} ({$emails})");
        }
        $this->newLine();

        $this->info('Next Steps:');
        $this->line('  1. Test the Gmail query in your Gmail UI to ensure it returns emails');
        $this->line('  2. Run the automation manually to test:');
        $this->line('     App\Domains\Automation\Services\LogerAutomationService::run($automation);');
        $this->line('  3. Monitor logs for any errors:');
        $this->line('     tail -f storage/logs/laravel.log');
        $this->line('  4. Disable old bank-specific automations after confirming this works');
        $this->newLine();

        $this->info('Troubleshooting:');
        $this->line('  - Guide: .kiro/examples/UNIVERSAL_BANK_PARSER_GUIDE.md');
        $this->line('  - Logs: storage/logs/laravel.log');
        $this->line('  - Tests: php artisan test --filter=UniversalBankParserTest');
        $this->newLine();

        $this->info('Done! 🎉');

        return Command::SUCCESS;
    }
}
