<?php

namespace App\Domains\Integration\Services;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Actions\GmailReceived;
use App\Domains\Integration\Actions\TransactionCreateEntry;
use App\Domains\Integration\Actions\UniversalBankParser;
use App\Domains\Integration\Models\Integration;
use App\Models\Account;
use App\Models\ProcessedEmail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

        // Prefer the token-bearing Gmail integration so the automation is born
        // with a live link (avoids a null integration_id that the runner would
        // then have to self-heal); fall back to any Gmail row if none has a token.
        $integration ??= GoogleService::findGoogleIntegration($user->id, $teamId, true)
            ?? Integration::where('user_id', $user->id)
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

    public static function find(User $user, ?int $teamId = null): ?Automation
    {
        return Automation::where([
            'user_id' => $user->id,
            'team_id' => $teamId ?? $user->current_team_id,
            'name' => self::NAME,
        ])->first();
    }

    /**
     * State for the Integrations UI: whether bank-email import is on, whether it
     * has been set up, whether a Gmail account is connected, and when that
     * mailbox last synced (so the card can show a real "last synced" line).
     *
     * @return array{enabled: bool, exists: bool, connected: bool, last_synced_at: ?string, last_import_at: ?string, sync: array<string, mixed>|null}
     */
    public static function status(User $user, ?int $teamId = null): array
    {
        $automation = self::find($user, $teamId);
        $integration = GoogleService::findGoogleIntegration(
            $user->id,
            $teamId ?? $user->current_team_id,
            true
        );

        $teamId ??= $user->current_team_id;

        return [
            'enabled' => (bool) ($automation?->status),
            'exists' => (bool) $automation,
            'connected' => (bool) $integration,
            'last_synced_at' => $integration?->last_synced_at,
            'last_import_at' => self::lastImportDate($teamId, $automation?->id),
            'sync' => self::presentSync($teamId),
        ];
    }

    /**
     * A sync with no progress for this long is considered dead (the process was
     * killed) and can be started again.
     */
    public const SYNC_STALE_SECONDS = 120;

    /**
     * Time spent per step, so no single request runs long enough to be killed.
     */
    public const SYNC_STEP_SECONDS = 15;

    /**
     * Start (or resume) a catch-up sync and run its first step. Further steps run
     * each time the status is read while it is running (the page polls it), so
     * no long process and no queue is needed.
     *
     * @return array<string, mixed>
     */
    public static function startSync(User $user): array
    {
        $teamId = (int) $user->current_team_id;
        $automation = self::find($user, $teamId);
        if (! $automation || ! GoogleService::findGoogleIntegration($user->id, $teamId, true)) {
            throw ValidationException::withMessages(['sync' => __('Connect Gmail and turn on bank email import first.')]);
        }

        if (! self::syncIsRunning($teamId)) {
            self::rememberSync($teamId, [
                'status' => 'running',
                'since' => self::syncSince($teamId, $automation->id),
                'checked' => 0,
                'imported' => 0,
                'page_token' => null,
                'latest' => null,
                'processed_before' => ProcessedEmail::query()->where('team_id', $teamId)->count(),
            ]);
        }

        return self::continueSync($user);
    }

    /**
     * Run the next step of a running sync (no-op otherwise) and return its state.
     *
     * @return array<string, mixed>|null
     */
    public static function continueSync(User $user): ?array
    {
        $teamId = (int) $user->current_team_id;
        $state = Cache::get(self::syncCacheKey($teamId));
        if (($state['status'] ?? null) !== 'running' || ! self::syncIsRunning($teamId)) {
            return $state;
        }

        $lock = Cache::lock("bank-email-sync-lock:{$teamId}", self::SYNC_STEP_SECONDS * 4);
        if (! $lock->get()) {
            return $state;
        }

        try {
            $automation = self::find($user, $teamId) ?? throw new \RuntimeException('Bank email import is not set up.');
            $startedAt = microtime(true);
            do {
                $step = app(GmailReceived::class)->syncStep($automation, $state['since'], $state['page_token'], $state['latest']);
                $state['checked'] += $step['checked'];
                $state['page_token'] = $step['next_page_token'];
                $state['latest'] = $step['latest'];
            } while (! $step['done'] && microtime(true) - $startedAt < self::SYNC_STEP_SECONDS);

            $state['imported'] = ProcessedEmail::query()->where('team_id', $teamId)->count() - $state['processed_before'];
            if ($step['done']) {
                $state['status'] = 'done';
            }
        } catch (\Throwable $exception) {
            Log::error('Bank email sync failed', ['team_id' => $teamId, 'error' => $exception->getMessage()]);
            $state['status'] = 'failed';
            $state['error'] = $exception->getMessage();
        } finally {
            $lock->release();
        }

        self::rememberSync($teamId, $state);

        return Cache::get(self::syncCacheKey($teamId));
    }

    /**
     * What the UI sees of the last sync. A running sync without progress for a
     * while shows as «stalled», so the button can be pressed again.
     *
     * @return array{status: string, since: ?string, checked: int, imported: int, error: ?string, at: ?string}|null
     */
    public static function presentSync(int $teamId): ?array
    {
        $state = Cache::get(self::syncCacheKey($teamId));
        if (! $state) {
            return null;
        }

        $status = $state['status'] === 'running' && ! self::syncIsRunning($teamId) ? 'stalled' : $state['status'];

        return [
            'status' => $status,
            'since' => $state['since'] ?? null,
            'checked' => (int) ($state['checked'] ?? 0),
            'imported' => (int) ($state['imported'] ?? 0),
            'error' => $state['error'] ?? null,
            'at' => $state['at'] ?? null,
        ];
    }

    public static function syncIsRunning(int $teamId): bool
    {
        $state = Cache::get(self::syncCacheKey($teamId));

        return ($state['status'] ?? null) === 'running'
            && Carbon::parse($state['at'])->diffInSeconds(now(), true) < self::SYNC_STALE_SECONDS;
    }

    /**
     * Where a catch-up sync starts: a few days before the last imported bank
     * movement (emails can arrive late), or 30 days back if nothing was imported.
     */
    public static function syncSince(int $teamId, ?int $automationId): string
    {
        $last = self::lastImportDate($teamId, $automationId);

        return ($last ? Carbon::parse($last)->subDays(3) : now()->subDays(30))->format('Y-m-d');
    }

    /**
     * Date of the newest bank movement imported from email for the team.
     */
    public static function lastImportDate(int $teamId, ?int $automationId): ?string
    {
        $last = ProcessedEmail::query()
            ->where('processed_emails.team_id', $teamId)
            ->when($automationId, fn ($query) => $query->where('processed_emails.automation_id', $automationId))
            ->leftJoin('transactions', 'transactions.id', '=', 'processed_emails.transaction_id')
            ->selectRaw('MAX(COALESCE(transactions.date, processed_emails.created_at)) as last_date')
            ->value('last_date');

        return $last ? Carbon::parse($last)->format('Y-m-d') : null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public static function rememberSync(int $teamId, array $result): void
    {
        Cache::put(self::syncCacheKey($teamId), [...$result, 'at' => now()->toIso8601String()], now()->addDays(7));
    }

    public static function syncCacheKey(int $teamId): string
    {
        return "bank-email-sync:{$teamId}";
    }

    public static function enable(User $user, ?int $teamId = null): Automation
    {
        $existing = self::find($user, $teamId);
        if ($existing) {
            $existing->update(['status' => true]);

            return $existing;
        }

        // No automation yet (mailbox connected before this feature, or it was
        // never provisioned) — build it now.
        $automation = self::setup($user, null, null, true);

        return $automation ?? throw new \RuntimeException('Could not set up the bank automation. Add an account or reconnect Gmail.');
    }

    public static function disable(User $user, ?int $teamId = null): void
    {
        self::find($user, $teamId)?->update(['status' => false]);
    }
}
