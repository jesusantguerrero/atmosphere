<?php

namespace App\Console\Commands;

use App\Domains\Budget\Services\BudgetRolloverService;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gives a transaction its journal lines when it never got any.
 *
 * Background
 * ----------
 * The lines (`transaction_lines`) are what account balances, the budget's activity, Ready to Assign and the
 * reconciliations are summed from. Until now the mobile API created the transaction row (`POST /transactions` and
 * `POST /transfers`) without creating its lines, so what was entered from the app moved nothing. New ones get
 * their lines on their own; this fixes the ones already in the books.
 *
 * What it does
 * ------------
 * For every transaction that has NO line at all it writes the same lines the journal writes for a transaction
 * created on the web: one on the account (the money going out or in) and its counter line (the payee's account, or
 * the other account of a transfer). It does not fire any event (no notifications, no automations, no linking of
 * shopping trips), and it never touches a transaction that already has lines, so it is safe to run again: a second
 * run finds nothing.
 *
 * What it leaves alone (and reports): planned or other non-posted statuses, transfers already flagged as such,
 * transactions in a currency other than the account's (multi-currency keeps its own balances), and rows with no
 * valid account or a zero total.
 *
 * After writing, the budget is rolled forward from the oldest affected month so the numbers match the new lines.
 *
 * Usage
 * -----
 *   php artisan transactions:backfill-lines                       # report only (nothing is written)
 *   php artisan transactions:backfill-lines --team=42             # one team
 *   php artisan transactions:backfill-lines --apply               # write, asking first
 *   php artisan transactions:backfill-lines --apply --force       # write without asking
 *   php artisan transactions:backfill-lines --apply --no-rollover # write, recompute the budget yourself later
 */
class BackfillTransactionLines extends Command
{
    protected $signature = 'transactions:backfill-lines
        {--apply : Write the lines. Without it nothing changes: it only reports what it would do}
        {--force : With --apply, do not ask for confirmation}
        {--team= : Limit to a single team_id}
        {--from= : Only transactions dated on or after this day (YYYY-MM-DD)}
        {--limit=0 : Stop after this many transactions (0 = all)}
        {--no-rollover : Do not roll the budget forward afterwards}';

    protected $description = 'Create the journal lines of transactions that were saved without any (the ones made from the mobile app)';

    public function handle(BudgetRolloverService $rollover): int
    {
        $from = $this->option('from');
        if ($from && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $from)) {
            $this->error('--from must be a day like 2026-01-31.');

            return self::INVALID;
        }

        [$eligible, $skipped] = $this->scan((int) $this->option('team'), $from ? (string) $from : null, (int) $this->option('limit'));

        $this->line('');
        $this->info('Transactions without lines');
        $this->table(
            ['Team', 'To fix', 'Oldest', 'Skipped'],
            $eligible->groupBy('team_id')->map(fn ($rows, $team) => [
                $team,
                $rows->count(),
                $rows->min('date'),
                collect($skipped[$team] ?? [])->map(fn ($count, $reason) => "$reason: $count")->implode(', ') ?: '-',
            ])->values()->all(),
        );
        foreach ($skipped as $team => $reasons) {
            if (! $eligible->contains('team_id', $team)) {
                $this->line("Team $team: nothing to fix; skipped ".collect($reasons)->map(fn ($count, $reason) => "$reason: $count")->implode(', ').'.');
            }
        }

        if ($eligible->isEmpty()) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        $this->line('Examples:');
        $this->table(['Id', 'Date', 'Total', 'Direction', 'Description'], $eligible->take(8)->map(fn ($row) => [$row->id, substr((string) $row->date, 0, 10), $row->total, $row->direction, mb_strimwidth((string) $row->description, 0, 50, '…')])->all());

        if (! $this->option('apply')) {
            $this->warn("Would write lines for {$eligible->count()} transactions. Nothing was changed; run it again with --apply to do it.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Write lines for {$eligible->count()} transactions in {$eligible->pluck('team_id')->unique()->count()} team(s)?", false)) {
            $this->line('Cancelled. Nothing was changed.');

            return self::SUCCESS;
        }

        $done = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($eligible->count());
        foreach ($eligible as $row) {
            try {
                DB::transaction(function () use ($row) {
                    $transaction = Transaction::query()->whereDoesntHave('lines')->find($row->id);
                    // Someone gave it lines meanwhile (or deleted it): not ours to touch.
                    $transaction?->createLines([], $transaction->toArray());
                });
                $done++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('transactions:backfill-lines failed', ['transaction_id' => $row->id, 'error' => $e->getMessage()]);
                $this->newLine();
                $this->error("Transaction {$row->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);
        $this->info("Wrote lines for $done transactions".($failed ? " ($failed failed, see the log)." : '.'));

        if (! $this->option('no-rollover')) {
            foreach ($eligible->groupBy('team_id') as $team => $rows) {
                $month = Carbon::parse($rows->min('date'))->format('Y-m');
                try {
                    $rollover->startFrom((int) $team, $month);
                    $this->line("Team $team: budget rolled forward from $month.");
                } catch (\Throwable $e) {
                    $this->error("Team $team: the budget could not be rolled forward ({$e->getMessage()}). Run the rollover for it from $month.");
                }
            }
        }

        $this->line('Run it again without --apply to check that nothing is left.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: array<int, array<string, int>>} the transactions to fix and,
     *                                                                                      per team, how many were skipped and why
     */
    private function scan(int $team, ?string $from, int $limit): array
    {
        $eligible = collect();
        $skipped = [];
        $accounts = [];

        $query = Transaction::query()
            ->whereDoesntHave('lines')
            ->select(['transactions.id', 'team_id', 'account_id', 'date', 'total', 'direction', 'status', 'is_transfer', 'currency_code', 'exchange_rate', 'description'])
            ->when($team, fn ($q) => $q->where('team_id', $team))
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->orderBy('team_id')
            ->orderBy('date')
            ->orderBy('id');

        $query->chunkById(500, function ($rows) use (&$eligible, &$skipped, &$accounts, $limit) {
            foreach ($rows as $row) {
                if ($limit && $eligible->count() >= $limit) {
                    return false;
                }
                $account = $accounts[$row->account_id] ??= Account::query()->find($row->account_id);

                $reason = match (true) {
                    ! in_array($row->status, [Transaction::STATUS_VERIFIED, Transaction::STATUS_DRAFT], true) => "status {$row->status}",
                    (bool) $row->is_transfer => 'flagged transfer',
                    $row->exchange_rate !== null => 'multi-currency',
                    ! $account || (int) $account->team_id !== (int) $row->team_id => 'no valid account',
                    $row->currency_code && $account->currency_code && $row->currency_code !== $account->currency_code => 'other currency',
                    (float) $row->total <= 0 => 'zero total',
                    default => null,
                };

                if ($reason) {
                    $skipped[$row->team_id][$reason] = ($skipped[$row->team_id][$reason] ?? 0) + 1;

                    continue;
                }
                $eligible->push($row);
            }
        }, 'transactions.id', 'id');

        return [$eligible, $skipped];
    }
}
