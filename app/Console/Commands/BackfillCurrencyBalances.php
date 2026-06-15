<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\CurrencyBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Transaction;

/**
 * Recompute `currency_balances.pending_balance` from the transactions table.
 *
 * Background
 * ----------
 * `MultiCurrencyTransactionService` is the only path that increments
 * `currency_balances.pending_balance` when a secondary-currency tx is created
 * on a multi-currency account. Transactions created BEFORE the user toggled
 * `is_multi_currency=true` on the account — or imported via paths that don't
 * route through that service — never landed in the cache. The
 * MultiCurrencyDetailPanel then shows $0 in the secondary column even though
 * the underlying transactions exist.
 *
 * This command is idempotent: it recomputes the cache from scratch by summing
 * `transaction_lines.amount * type` joined to `transactions` filtered by
 * `currency_code`, for every (multi-currency account, secondary currency) pair.
 * Safe to re-run.
 *
 * Usage
 * -----
 *   php artisan multicurrency:backfill-balances           # all teams
 *   php artisan multicurrency:backfill-balances --team=42 # one team
 *   php artisan multicurrency:backfill-balances --dry     # report only
 */
class BackfillCurrencyBalances extends Command
{
    protected $signature = 'multicurrency:backfill-balances
        {--team= : Limit to a single team_id}
        {--dry : Compute and print but do not write to currency_balances}
        {--list-txs : List every tx contributing to each per-currency sum (date, payee, total)}';

    protected $description = 'Recompute currency_balances.pending_balance from transactions for multi-currency accounts';

    public function handle(): int
    {
        $teamFilter = $this->option('team');
        $dryRun = (bool) $this->option('dry');
        $listTxs = (bool) $this->option('list-txs');

        $query = Account::query()->where('is_multi_currency', true);
        if ($teamFilter) {
            $query->where('team_id', (int) $teamFilter);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->info('No multi-currency accounts found.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d multi-currency account(s)%s.',
            $dryRun ? 'Dry-run for' : 'Backfilling',
            $accounts->count(),
            $teamFilter ? " on team {$teamFilter}" : ''
        ));

        $updated = 0;
        $skipped = 0;

        foreach ($accounts as $account) {
            $primary = $account->getPrimaryCurrency();
            $secondaries = $account->getSecondaryCurrencies();

            if (empty($secondaries)) {
                $skipped++;
                continue;
            }

            foreach ($secondaries as $currency) {
                if ($currency === $primary) {
                    continue;
                }

                $pending = $this->computePendingBalance($account->id, $currency);

                $this->line(sprintf(
                    '  [%d] %s — %s: pending=%s',
                    $account->id,
                    $account->name,
                    $currency,
                    number_format($pending, 2)
                ));

                if ($listTxs) {
                    $this->printContributingTxs($account->id, $currency);
                }

                if ($dryRun) {
                    continue;
                }

                $row = CurrencyBalance::findOrCreate(
                    $account->id,
                    $currency,
                    $account->team_id,
                    $account->user_id
                );

                $row->pending_balance = $pending;
                $row->save();

                $updated++;
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? "Dry-run complete. Would update {$updated} row(s)."
            : "Backfill complete. Updated {$updated} currency balance row(s). Skipped {$skipped} account(s) with no secondaries."
        );

        return self::SUCCESS;
    }

    /**
     * Sum signed amounts of all non-canceled transaction_lines for this
     * account that are tagged with the given currency_code on their parent
     * transaction. Matches the math the vendor's `getBalanceAttribute()`
     * uses, just narrowed to one currency.
     */
    private function computePendingBalance(int $accountId, string $currencyCode): float
    {
        return (float) DB::table('transaction_lines as tl')
            ->join('transactions as t', 't.id', '=', 'tl.transaction_id')
            ->where('tl.account_id', $accountId)
            ->where('t.currency_code', $currencyCode)
            ->where('t.status', '!=', Transaction::STATUS_CANCELED)
            ->sum(DB::raw('tl.amount * tl.type'));
    }

    /**
     * Print every tx contributing to the per-currency sum so the user can
     * audit unexpected totals (e.g. legacy seed data, double-imports). One
     * line per transaction_line with date, payee, signed amount, status.
     */
    private function printContributingTxs(int $accountId, string $currencyCode): void
    {
        $rows = DB::table('transaction_lines as tl')
            ->join('transactions as t', 't.id', '=', 'tl.transaction_id')
            ->leftJoin('payees as p', 'p.id', '=', 't.payee_id')
            ->where('tl.account_id', $accountId)
            ->where('t.currency_code', $currencyCode)
            ->where('t.status', '!=', Transaction::STATUS_CANCELED)
            ->orderByDesc('t.date')
            ->orderByDesc('t.id')
            ->get([
                't.id as tx_id',
                't.date',
                't.description',
                't.status',
                'p.name as payee_name',
                'tl.amount',
                'tl.type',
            ]);

        if ($rows->isEmpty()) {
            $this->line('       (no transactions found)');

            return;
        }

        $running = 0.0;
        foreach ($rows as $row) {
            $signed = (float) $row->amount * (int) $row->type;
            $running += $signed;

            $this->line(sprintf(
                '       %s  #%-7d  %-30s  %s%-12s  %-8s  running=%s',
                $row->date,
                $row->tx_id,
                $this->shorten($row->payee_name ?: $row->description ?: '-', 30),
                $signed >= 0 ? '+' : '',
                number_format($signed, 2),
                $row->status,
                number_format($running, 2)
            ));
        }
    }

    private function shorten(string $value, int $max): string
    {
        return strlen($value) > $max ? substr($value, 0, $max - 1).'…' : $value;
    }
}
