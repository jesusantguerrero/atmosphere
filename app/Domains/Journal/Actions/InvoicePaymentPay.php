<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\InvoicePaymentMarkAsPaid;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Invoice\Invoice;

class InvoicePaymentPay implements InvoicePaymentMarkAsPaid
{
   
    public function markAsPaid(User $user, Invoice $invoice)
    {
        $this->validate($user, $invoice);
        return $invoice->markAsPaid();
    }

    public function validate(mixed $user, Invoice $invoice)
    {
        Gate::forUser($user)->authorize('update', Category::class);   
    }
}
