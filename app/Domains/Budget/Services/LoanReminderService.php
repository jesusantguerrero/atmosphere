<?php

namespace App\Domains\Budget\Services;

use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Transaction\Models\TransactionLine;
use App\Models\User;
use App\Notifications\LoanPaymentAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LoanReminderService
{
    public function sendDueReminders(?Carbon $date = null): int
    {
        $today = ($date ?? Carbon::today())->copy()->startOfDay();
        $targets = BudgetTarget::query()
            ->where('target_type', BudgetTarget::TYPE_LOAN)
            ->where('notify', true)
            ->whereNull('completed_at')
            ->whereIn('frequency', ['monthly', 'MONTHLY'])
            ->whereBetween('frequency_month_date', [1, 31])
            ->get();
        $users = User::query()->whereIn('id', $targets->pluck('user_id'))->get()->keyBy('id');
        $sent = 0;
        foreach ($targets as $target) {
            $user = $users->get($target->user_id);
            if (! $user) {
                continue;
            }
            foreach ([$today->copy()->startOfMonth(), $today->copy()->addMonthNoOverflow()->startOfMonth()] as $month) {
                $dueDate = $month->copy()->day(min((int) $target->frequency_month_date, $month->daysInMonth));
                if ($today->lt($dueDate->copy()->subDays(3)) || $today->gte($dueDate)
                    || ($target->loan_start_date && $dueDate->lt($target->loan_start_date))) {
                    continue;
                }
                $sent += (int) Cache::lock("loan-reminder:{$target->id}:{$dueDate->toDateString()}", 60)->get(function () use ($target, $user, $dueDate): bool {
                    if ($user->notifications()->where('type', LoanPaymentAlert::class)
                        ->where('data->target_id', $target->id)
                        ->where('data->due_date', $dueDate->toDateString())->exists()) {
                        return false;
                    }
                    $paid = $this->paidAmount($target, $dueDate);
                    if ((float) $target->amount > 0 && (float) $paid >= (float) $target->amount) {
                        return false;
                    }
                    $user->notify(new LoanPaymentAlert($target, $dueDate));

                    return true;
                });
            }
        }

        return $sent;
    }

    public function paidAmount(BudgetTarget $target, Carbon $dueDate): float
    {
        return (float) TransactionLine::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_lines.transaction_id')
            ->where('transaction_lines.team_id', $target->team_id)
            ->where('transaction_lines.category_id', $target->category_id)
            ->where('transaction_lines.type', -1)
            ->where('transactions.status', 'verified')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('transaction_lines.date', [$dueDate->copy()->startOfMonth()->toDateString(), $dueDate->toDateString()])
            ->sum('transaction_lines.amount');
    }
}
