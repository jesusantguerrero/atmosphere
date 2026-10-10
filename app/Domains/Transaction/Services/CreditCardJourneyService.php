<?php

namespace App\Domains\Transaction\Services;

use App\Domains\Budget\Data\BudgetReservedNames;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Insane\Journal\Models\Core\Transaction;
use Insane\Journal\Models\Core\TransactionLine;

class CreditCardJourneyService
{
    public function report(int $teamId, string $startDate, string $endDate, ?array $accountIds = null): array
    {
        $accounts = Account::query()->where('team_id', $teamId)
            ->whereNotNull('credit_closing_day')
            ->when(! empty($accountIds), fn ($query) => $query->whereIn('id', $accountIds))
            ->get();
        $lines = TransactionLine::query()->withoutEagerLoads()
            ->join('transactions', 'transactions.id', '=', 'transaction_lines.transaction_id')
            ->join('accounts', 'accounts.id', '=', 'transaction_lines.account_id')
            ->whereColumn('transactions.currency_code', 'accounts.currency_code')
            ->where('transaction_lines.team_id', $teamId)->whereIn('transaction_lines.account_id', $accounts->pluck('id'))
            ->whereDate('transaction_lines.date', '<=', $endDate)
            ->whereHas('transaction', fn ($query) => $query->where('status', Transaction::STATUS_VERIFIED))
            ->select(['transaction_lines.account_id', 'transaction_lines.date'])
            ->selectRaw('SUM(transaction_lines.amount * transaction_lines.type) as net_amount')
            ->groupBy('transaction_lines.account_id', 'transaction_lines.date')->get()->groupBy('account_id');

        $events = [];
        foreach ($accounts as $account) {
            foreach (['credit_opened_at' => 'opened', 'closed_at' => 'closed'] as $field => $kind) {
                if (! $account->$field || Carbon::parse($account->$field)->toDateString() > min($endDate, now()->toDateString())) {
                    continue;
                }
                $date = Carbon::parse($account->$field)->toDateString();
                $monthOnly = $kind === 'opened' && $account->credit_opened_precision === 'month';
                $snapshotDate = $monthOnly ? min(Carbon::parse($date)->endOfMonth()->toDateString(), now()->toDateString(), $endDate) : $date;
                $beforeDate = $monthOnly ? Carbon::parse($date)->startOfMonth()->subDay()->toDateString() : Carbon::parse($date)->subDay()->toDateString();
                $events[] = [
                    'id' => $account->id.'-'.$kind, 'account_id' => $account->id,
                    'date' => $date, 'kind' => $kind, 'name' => $account->name,
                    'date_precision' => $monthOnly ? 'month' : 'day', 'snapshot_date' => $snapshotDate,
                    'cards' => $this->snapshot($accounts, $lines, $snapshotDate),
                    'before_cards' => $this->snapshot($accounts, $lines, $beforeDate),
                ];
            }
        }
        usort($events, fn ($left, $right) => strcmp($left['date'], $right['date']));
        $snapshotDate = min($endDate, now()->toDateString());
        if ($accounts->isNotEmpty()) {
            $events[] = [
                'id' => 'period-snapshot', 'account_id' => null, 'date' => $snapshotDate,
                'kind' => $snapshotDate === now()->toDateString() ? 'today' : 'period', 'name' => '',
                'cards' => $this->snapshot($accounts, $lines, $snapshotDate, $snapshotDate === now()->toDateString()),
                'before_cards' => [],
            ];
        }

        $purchases = TransactionLine::query()->withoutEagerLoads()
            ->join('transactions', 'transactions.id', '=', 'transaction_lines.transaction_id')
            ->join('accounts', 'accounts.id', '=', 'transaction_lines.account_id')
            ->whereColumn('transactions.currency_code', 'accounts.currency_code')
            ->where('transaction_lines.team_id', $teamId)->whereIn('transaction_lines.account_id', $accounts->pluck('id'))
            ->whereBetween('transaction_lines.date', [$startDate, Carbon::parse($endDate)->endOfDay()])
            ->where('transaction_lines.type', -1)->whereNotNull('transaction_lines.category_id')
            ->whereHas('category', fn ($query) => $query->where('name', '<>', BudgetReservedNames::READY_TO_ASSIGN->value))
            ->whereHas('transaction', fn ($query) => $query->where('status', Transaction::STATUS_VERIFIED)->where('is_transfer', false))
            ->select(['transaction_lines.account_id', 'transaction_lines.category_id'])->selectRaw('SUM(transaction_lines.amount) as spent')
            ->groupBy('transaction_lines.account_id', 'transaction_lines.category_id')->get()->groupBy('account_id');
        $rewards = $accounts->map(function ($account) use ($purchases, $startDate, $endDate): array {
            $rules = $account->credit_rewards ?? [];
            $records = $purchases->get($account->id) ?? collect();
            $points = null;
            if (($rules['points'] ?? 0) > 0 && ($rules['spend'] ?? 0) > 0) {
                $points = $records->sum(function ($line) use ($rules): float {
                    $rule = collect($rules['category_rates'] ?? [])->firstWhere('category_id', $line->category_id) ?? $rules;

                    return (float) $line->spent / (float) $rule['spend'] * (float) $rule['points'];
                });
            }

            return [
                'id' => $account->id, 'name' => $account->name, 'currency' => $account->currency_code,
                'spent' => (float) $records->sum('spent'), 'points' => $points === null ? null : (int) floor($points),
                'estimated_value' => $points !== null && ($rules['point_value'] ?? 0) > 0 ? floor($points) * $rules['point_value'] : null,
                'rules' => $rules, 'multi_currency' => (bool) $account->is_multi_currency,
                'opened_at' => $account->credit_opened_at?->toDateString(), 'closed_at' => $account->closed_at?->toDateString(),
                'active_in_period' => (! $account->credit_opened_at || $account->credit_opened_at->toDateString() <= $endDate)
                    && (! $account->closed_at || $account->closed_at->toDateString() >= $startDate),
                'closed_before_period' => $account->closed_at !== null && $account->closed_at->toDateString() < $startDate
                    && (! $account->credit_opened_at || $account->credit_opened_at->toDateString() <= $endDate),
            ];
        })->values()->all();

        return ['events' => $events, 'cards' => $rewards, 'missing_opening_dates' => $accounts->whereNull('credit_opened_at')->count()];
    }

    private function snapshot(Collection $accounts, Collection $lines, string $date, bool $includeUnknown = false): array
    {
        return $accounts->filter(fn ($card) => $this->activeOn($card, $date)
            || ($includeUnknown && ! $card->credit_opened_at && ! $card->archived
                && (! $card->closed_at || $card->closed_at->toDateString() > $date)))
            ->map(function ($card) use ($lines, $date): array {
                $records = ($lines->get($card->id) ?? collect())->filter(fn ($line) => Carbon::parse($line->date)->toDateString() <= $date);

                return [
                    'id' => $card->id, 'name' => $card->name, 'currency' => $card->currency_code,
                    'balance' => $records->isEmpty() ? null : max(0, -(float) $records->sum('net_amount')),
                    'opening_known' => $card->credit_opened_at !== null,
                ];
            })->values()->all();
    }

    private function activeOn(Account $card, string $date): bool
    {
        return $card->credit_opened_at !== null && $card->credit_opened_at->toDateString() <= $date
            && (! $card->closed_at || $card->closed_at->toDateString() > $date);
    }
}
