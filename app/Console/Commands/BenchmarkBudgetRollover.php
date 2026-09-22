<?php

namespace App\Console\Commands;

use App\Domains\Budget\Services\BudgetRolloverService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Measures a budget rollover so we can decide where the cost really is before
 * optimizing (both rollover analyses in planning/exercises/2 agree: measure
 * first). Reports wall time, SQL time and query counts (selects vs writes).
 *
 * Non-destructive by default: the rollover runs inside a transaction that is
 * rolled back, so it can be pointed at real data safely. Pass --persist to
 * actually keep the result (i.e. run it for real).
 *
 * Usage:
 *   php artisan budget:rollover-benchmark 7 2026-06
 *   php artisan budget:rollover-benchmark 7 2026-06 --json
 *   php artisan budget:rollover-benchmark 7 2026-06 --persist
 */
class BenchmarkBudgetRollover extends Command
{
    protected $signature = 'budget:rollover-benchmark
                            {team : Team id}
                            {month : Start month, YYYY-MM}
                            {--persist : Persist the rollover instead of rolling it back (default: measure only)}
                            {--json : Machine-readable output}';

    protected $description = 'Measure a budget rollover (wall time, SQL time, query counts). Non-destructive unless --persist.';

    public function handle(BudgetRolloverService $rolloverService): int
    {
        $teamId = (int) $this->argument('team');
        $month = (string) $this->argument('month');
        $persist = (bool) $this->option('persist');

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->error("Month must be YYYY-MM, got: {$month}");

            return self::INVALID;
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        DB::beginTransaction();

        $startedAt = hrtime(true);
        try {
            $rolloverService->startFrom($teamId, $month);
        } finally {
            $wallMs = (hrtime(true) - $startedAt) / 1e6;
            $persist ? DB::commit() : DB::rollBack();
            DB::disableQueryLog();
        }

        $log = DB::getQueryLog();
        $selects = 0;
        $writes = 0;
        $sqlMs = 0.0;
        foreach ($log as $q) {
            $sqlMs += (float) ($q['time'] ?? 0);
            $verb = strtolower((string) strtok(ltrim((string) $q['query']), " \t\r\n("));
            $verb === 'select' ? $selects++ : $writes++;
        }

        $result = [
            'team_id' => $teamId,
            'from_month' => $month,
            'persisted' => $persist,
            'queries_total' => count($log),
            'queries_select' => $selects,
            'queries_write' => $writes,
            'sql_time_ms' => round($sqlMs, 1),
            'wall_time_ms' => round($wallMs, 1),
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info("Rollover benchmark — team {$teamId}, from {$month}".($persist ? '  (PERSISTED)' : '  (measured, rolled back)'));
        $this->table(['Metric', 'Value'], [
            ['Queries total', $result['queries_total']],
            ['  selects', $selects],
            ['  writes', $writes],
            ['SQL time (ms)', $result['sql_time_ms']],
            ['Wall time (ms)', $result['wall_time_ms']],
        ]);
        $this->newLine();
        $this->line('Run the same team/month before and after an optimization to compare. Default measures only; add --persist to keep the result.');

        return self::SUCCESS;
    }
}
