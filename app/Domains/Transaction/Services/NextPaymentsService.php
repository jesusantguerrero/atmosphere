<?php

namespace App\Domains\Transaction\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\BillingCycle;

class NextPaymentsService
{
    public function getNextPayments(int $teamId, ?string $date = null): Collection
    {
        $currentDate = $date ? Carbon::parse($date) : Carbon::now();
        $endDate = $currentDate->copy()->addMonths(2)->endOfMonth();

        return collect([
            ...$this->getUnpaidBudgetCategories($teamId, $currentDate, $endDate),
            ...$this->getUpcomingCreditCardPayments($teamId, $currentDate, $endDate),
            ...$this->getPlannedTransactions($teamId, $currentDate, $endDate),
        ])
        ->sortBy('due_date')
        ->values();
    }

    private function getUnpaidBudgetCategories(int $teamId, Carbon $startDate, Carbon $endDate): array
    {
        $currentMonth = $startDate->format('Y-m-01');

        // Get budget categories with targets that haven't been paid this month
        $unpaidBudgets = BudgetTarget::where('team_id', $teamId)
            ->where([
                'target_type' => 'spending',
                'notify' => 1
            ])
            ->whereNotNull('frequency_month_date')
            ->with(['category'])
            ->get()
            ->filter(function ($target) use ($teamId, $currentMonth) {
                // Check if there's already a transaction for this category this month
                $hasTransaction = Transaction::where('team_id', $teamId)
                    ->where('category_id', $target->category_id)
                    ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [substr($currentMonth, 0, 7)])
                    ->exists();

                return !$hasTransaction;
            })
            ->map(function ($target) use ($startDate) {
                $dueDay = min($target->frequency_month_date, $startDate->daysInMonth);
                $dueDate = $startDate->copy()->day($dueDay);

                return [
                    'id' => "budget_{$target->id}",
                    'type' => 'budget_category',
                    'description' => $target->category->name ?? 'Budget Category',
                    'title' => $target->category->name ?? 'Budget Category',
                    'total' => $target->amount ?? 0,
                    'due_date' => $dueDate ? $dueDate->format('Y-m-d') : null,
                    'date' => $dueDate ? $dueDate->format('Y-m-d') : null,
                    'category_id' => $target->category_id,
                    'category_name' => $target->category->name,
                    'status' => 'pending',
                    'source' => 'budget_target',
                    'metadata' => [
                        'target_id' => $target->id,
                        'frequency' => $target->frequency,
                    ]
                ];
            })
            ->toArray();

        return $unpaidBudgets;
    }

    private function getUpcomingCreditCardPayments(int $teamId, Carbon $startDate, Carbon $endDate): array
    {
        // Get all credit card accounts (accounts with credit_closing_day set)
        $creditCardAccounts = \App\Models\Account::where([
            'team_id' => $teamId,
        ])
        ->whereNull('closed_at')
        ->whereNotNull('credit_closing_day')
        ->get();

        $upcomingPayments = [];
        $closings = [];
        foreach ($creditCardAccounts as $account) {
            $closingDay = $account->credit_closing_day;

            // Get current balance of the credit card
            $currentBalance = $account->balance;

            // For credit cards, negative balance means debt (money owed)
            $currentDebt = abs(min(0, $currentBalance));
            $closings[$account->name] = [
                "name" => $account->name,
                "debt" => $currentDebt,
            ];
            if ($currentDebt > 0) {

                $today = Carbon::now();
                $currentMonth = $today->copy()->startOfMonth();

                // Find the closing date for this month
                $closingDate = $currentMonth->copy()->day(min($closingDay, $currentMonth->daysInMonth));
                $closings[$account->name]["date"] = $closingDate;

                // Find the previous closing date (last month)
                $previousClosingDate = $currentMonth->copy()->subMonth()->day(min($closingDay, $currentMonth->subMonth()->daysInMonth));

                // Check if there was a payment (type = 1) since the previous closing date (i.e., during current statement period)
                $paymentInCurrentPeriod = \App\Domains\Transaction\Models\TransactionLine::where('account_id', $account->id)
                    ->where('date', '>', $previousClosingDate->format('Y-m-d'))
                    ->where('type', 1) // type = 1 means payment/income
                    ->exists();

                // Only show if no payment was made during this statement period
                if (!$paymentInCurrentPeriod) {
                    $upcomingPayments[] = [
                        'id' => "cc_payment_{$account->id}_{$closingDate->format('Y-m')}",
                        'type' => 'credit_card_payment',
                        'title' => "Credit Card Payment - {$account->name}",
                        'description' => "Credit Card Payment - {$account->name}",
                        'total' => $currentDebt,
                        'due_date' => $closingDate->format('Y-m-d'),
                        'date' => $closingDate->format('Y-m-d'),
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'status' => $today->gte($closingDate) ? 'overdue' : 'pending',
                        'source' => 'dynamic_calculation',
                        'metadata' => [
                            'total_debt' => $currentDebt,
                            'closing_day' => $closingDay,
                            'current_balance' => $currentBalance,
                        ]
                    ];
                }
            }
        }

        return $upcomingPayments;
    }

    private function getPlannedTransactions(int $teamId, Carbon $startDate, Carbon $endDate): array
    {
        return Transaction::where('team_id', $teamId)
            ->planned()
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNull('resource_id')
            ->whereHas('schedule', function ($query) {
                $query->whereNull('completed_at');
            })
            ->with(['category', 'payee'])
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => "planned_{$transaction->id}",
                    'type' => 'planned_transaction',
                    'title' => $transaction->description ?? $transaction->payee?->name ?? $transaction->category?->name,
                    'description' => $transaction->description ?? $transaction->payee?->name ?? $transaction->category?->name,
                    'total' => abs($transaction->total),
                    'due_date' => $transaction->date ? $transaction->date : null,
                    'date' => $transaction->date ? $transaction->date : null,
                    'category_id' => $transaction->category_id,
                    'category_name' => $transaction->category?->name,
                    'payee_name' => $transaction->payee?->name,
                    'status' => $transaction->status,
                    'source' => 'planned_transaction',
                    'metadata' => [
                        'transaction_id' => $transaction->id,
                        'description' => $transaction->description,
                    ]
                ];
            })
            ->toArray();
    }

    public function markAsPaid(string $paymentId, array $transactionData): bool
    {
        $parts = explode('_', $paymentId);
        $type = $parts[0];

        // Handle different ID formats
        if ($type === 'cc' && isset($parts[1]) && $parts[1] === 'payment') {
            // Format: cc_payment_{account_id}_{month}
            $accountId = $parts[2] ?? null;
            return $this->markCreditCardPaymentAsPaid($accountId, $transactionData);
        }

        $id = $parts[1] ?? null;

        switch ($type) {
            case 'budget':
                return $this->markBudgetCategoryAsPaid($id, $transactionData);
            case 'billing':
                return $this->markBillingCycleAsPaid($id, $transactionData);
            case 'planned':
                return $this->markPlannedTransactionAsPaid($id, $transactionData);
            default:
                return false;
        }
    }

    private function markBudgetCategoryAsPaid(int $targetId, array $transactionData): bool
    {
        $target = BudgetTarget::find($targetId);
        if (!$target) return false;

        // Create actual transaction
        $transaction = Transaction::create([
            ...$transactionData,
            'category_id' => $target->category_id,
            'team_id' => $target->team_id,
            'status' => Transaction::STATUS_VERIFIED,
        ]);

        return $transaction->exists;
    }

    private function markBillingCycleAsPaid(int $cycleId, array $transactionData): bool
    {
        $cycle = BillingCycle::find($cycleId);
        if (!$cycle) return false;

        // Create payment transaction and link to billing cycle
        $transaction = Transaction::create([
            ...$transactionData,
            'account_id' => $cycle->account_id,
            'team_id' => $cycle->team_id,
            'status' => Transaction::STATUS_VERIFIED,
        ]);

        $cycle->update(['status' => 'paid']);

        return $transaction->exists;
    }

    private function markPlannedTransactionAsPaid(int $transactionId, array $transactionData): bool
    {
        $plannedTransaction = Transaction::find($transactionId);
        if (!$plannedTransaction) return false;

        // Update planned transaction or create new verified transaction
        $plannedTransaction->update([
            ...$transactionData,
            'status' => Transaction::STATUS_VERIFIED,
        ]);

        // Mark schedule as completed
        $plannedTransaction->schedule()->update(['completed_at' => now()]);

        return true;
    }

    private function markCreditCardPaymentAsPaid(?string $accountId, array $transactionData): bool
    {
        if (!$accountId) return false;

        $account = \App\Models\Account::find($accountId);
        if (!$account) return false;

        // Create payment transaction for the credit card
        $transaction = Transaction::create([
            ...$transactionData,
            'account_id' => $account->id,
            'team_id' => $account->team_id,
            'status' => Transaction::STATUS_VERIFIED,
        ]);

        return $transaction->exists;
    }
}
