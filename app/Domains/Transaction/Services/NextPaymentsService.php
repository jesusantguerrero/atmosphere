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
                    'description' => $target->category->name,
                    'title' => $target->category->name,
                    'total' => $target->amount,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'date' => $dueDate->format('Y-m-d'),
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
        return BillingCycle::where('team_id', $teamId)
            ->whereBetween('due_at', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('status', ['pending', 'overdue'])
            ->with(['account'])
            ->get()
            ->map(function ($cycle) {
                return [
                    'id' => "billing_{$cycle->id}",
                    'type' => 'credit_card_payment',
                    'title' => "Credit Card Payment - {$cycle->account->name}",
                    'amount' => $cycle->minimum_payment ?? $cycle->total,
                    'due_date' => $cycle->due_at,
                    'account_id' => $cycle->account_id,
                    'account_name' => $cycle->account->name,
                    'status' => $cycle->status,
                    'source' => 'billing_cycle',
                    'metadata' => [
                        'cycle_id' => $cycle->id,
                        'total_debt' => $cycle->total,
                        'minimum_payment' => $cycle->minimum_payment,
                    ]
                ];
            })
            ->toArray();
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
                    'amount' => abs($transaction->total),
                    'due_date' => $transaction->date,
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
        [$type, $id] = explode('_', $paymentId, 2);

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
}