<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\InvoicePaymentDeletes;
use Insane\Journal\Models\Core\Payment;
use Insane\Journal\Models\Invoice\Invoice;

class InvoicePaymentDelete implements InvoicePaymentDeletes
{
    public function delete(User $user, Invoice $invoice, Payment $payment)
    {
        $this->validate($user, $invoice);
        $invoice->deletePayment($payment->id);
        $invoice->save();

        return $payment;
    }

    public function validate(User $user, Invoice $invoice)
    {
        Gate::forUser($user)->authorize('add-payment', Invoice::class);
    }
}
