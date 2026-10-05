<?php

namespace App\Notifications;

use App\Domains\Budget\Models\BudgetTarget;
use Carbon\Carbon;

class LoanPaymentAlert extends LogerNotification
{
    public function __construct(private BudgetTarget $target, private Carbon $dueDate) {}

    public function toArray($notifiable): array
    {
        return [
            'message' => __('Pay :name before :date.', ['name' => $this->target->name, 'date' => $this->dueDate->toDateString()]),
            'cta' => __('Review loan payment'),
            'link' => '/budgets',
            'target_id' => $this->target->id,
            'category_id' => $this->target->category_id,
            'due_date' => $this->dueDate->toDateString(),
        ];
    }
}
