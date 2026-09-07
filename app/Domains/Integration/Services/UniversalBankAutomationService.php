<?php

namespace App\Domains\Integration\Services;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Actions\GmailReceived;
use App\Domains\Integration\Actions\TransactionCreateEntry;
use App\Domains\Integration\Actions\UniversalBankParser;
use App\Domains\Integration\Models\Integration;
use App\Models\Account;
use App\Models\User;

class UniversalBankAutomationService
{
    public const NAME = 'Universal Bank Transaction Parser';

    /**
     * Provision the "Universal Bank Parser" automation for a user: a Gmail
     * trigger scoped to known bank senders -> parse the email -> create the
     * transaction. This is what lets a freshly connected mailbox start turning
     * bank emails into transactions with no manual automation building.
     *
     * Idempotent by (user, team, name). When $overwrite is false (the default,
     * used by the auto-setup on connect) an existing automation is returned
     * untouched so we never clobber a user's own edits; the CLI setup command
     * passes true to rebuild config + tasks.
     */
    public static function setup(
        User $user,
        ?Integration $integration = null,
        ?int $accountId = null,
        bool $overwrite = false
    ): ?Automation {
        $teamId = $user->current_team_id;
        if (! $teamId) {
            return null;
        }

        $existing = Automation::where([
            'user_id' => $user->id,
            'team_id' => $teamId,
            'name' => self::NAME,
        ])->first();

        if ($existing && ! $overwrite) {
            return $existing;
        }

        $integration ??= Integration::where('user_id', $user->id)
            ->whereHas('service', fn ($q) => $q->where('name', 'Gmail'))
            ->first();

        // The create-transaction action resolves a team account on its own when
        // this is blank, so a user with no account yet still gets a working
        // automation; we just pin their first account when there is one.
        $accountId ??= Account::where('user_id', $user->id)->value('id');

        $automation = Automation::updateOrCreate(
            [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'name' => self::NAME,
            ],
            [
                'integration_id' => $integration?->id,
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

        $automation->saveTasks([
            [
                'entity' => GmailReceived::class,
                'task_type' => 'trigger',
                'order' => 0,
                'name' => 'Gmail Trigger - All Banks',
                'values' => ['query' => UniversalBankParser::buildGmailQuery()],
            ],
            [
                'entity' => UniversalBankParser::class,
                'task_type' => 'component',
                'order' => 1,
                'name' => 'Parse Bank Email',
                'values' => [],
            ],
            [
                'entity' => TransactionCreateEntry::class,
                'task_type' => 'action',
                'order' => 2,
                'name' => 'Create Transaction',
                'values' => [
                    'account_id' => $accountId ? (string) $accountId : '',
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
        ]);

        return $automation;
    }
}
