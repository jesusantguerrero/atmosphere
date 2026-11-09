<?php

namespace App\Domains\Transaction\Services;

use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;

/**
 * How spending was paid: with a credit card or with cash/debit, by the type of
 * the account each verified expense line left from. Transfers are not spending.
 * Amounts are summed in each line's currency, like the rest of Insights.
 */
class SpendingByFundingService
{
    /**
     * @return list<array{category_id: int, name: string, total: float, credit_card: float, cash: float}>
     */
    public function categoriesByFunding(int $teamId, string $start, string $end): array
    {
        return $this->expenseLines($teamId, $start, $end)
            ->join('categories as c', 'c.id', '=', 'tl.category_id')
            ->where('c.name', '!=', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->selectRaw('tl.category_id, c.name, SUM(tl.amount) as total')
            ->selectRaw($this->creditCardSum().' as credit_card')
            ->selectRaw($this->cashSum().' as cash')
            ->groupBy('tl.category_id', 'c.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn (object $row) => [
                'category_id' => (int) $row->category_id,
                'name' => $row->name,
                'total' => round((float) (string) $row->total, 2),
                'credit_card' => round((float) (string) $row->credit_card, 2),
                'cash' => round((float) (string) $row->cash, 2),
            ])
            ->all();
    }

    /**
     * @return array{cash: float, credit_card: float}
     */
    public function spentByKind(int $teamId, string $start, string $end): array
    {
        $row = $this->expenseLines($teamId, $start, $end)
            ->selectRaw($this->creditCardSum().' as credit_card')
            ->selectRaw($this->cashSum().' as cash')
            ->first();

        return [
            'cash' => round((float) (string) ($row->cash ?? 0), 2),
            'credit_card' => round((float) (string) ($row->credit_card ?? 0), 2),
        ];
    }

    /**
     * Verified, non-transfer outflow lines from the team's money accounts in the period.
     */
    private function expenseLines(int $teamId, string $start, string $end): Builder
    {
        return DB::table('transaction_lines as tl')
            ->join('transactions as t', 't.id', '=', 'tl.transaction_id')
            ->join('accounts as a', 'a.id', '=', 'tl.account_id')
            ->join('account_detail_types as adt', 'adt.id', '=', 'a.account_detail_type_id')
            ->where('tl.team_id', $teamId)
            ->where('tl.type', -1)
            ->where('t.status', Transaction::STATUS_VERIFIED)
            ->whereNull('t.deleted_at')
            ->where(fn (Builder $query) => $query->where('t.is_transfer', false)->orWhereNull('t.is_transfer'))
            ->whereBetween('tl.date', [$start, $end])
            ->whereIn('adt.name', AccountDetailType::ALL);
    }

    private function creditCardSum(): string
    {
        return "SUM(CASE WHEN adt.name = '".AccountDetailType::CREDIT_CARD."' THEN tl.amount ELSE 0 END)";
    }

    private function cashSum(): string
    {
        $cash = implode("', '", AccountDetailType::ALL_CASH);

        return "SUM(CASE WHEN adt.name IN ('{$cash}') THEN tl.amount ELSE 0 END)";
    }
}
