<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\InvoicePaymentCreates;
use Insane\Journal\Models\Invoice\Invoice;

class InvoicePaymentCreate implements InvoicePaymentCreates
{
    public function create(User $user, Invoice $invoice, array $paymentData)
    {
        $this->validate($user, $invoice);
        $payment = $invoice->createPayment($paymentData);
        $invoice->save();

        return $payment;
    }

    public function validate(User $user, Invoice $invoice)
    {
        Gate::forUser($user)->authorize('add-payment', Invoice::class);
    }
}
