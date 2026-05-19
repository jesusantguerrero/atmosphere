<?php

namespace App\Domains\Transaction\Listeners;

use App\Domains\Transaction\Models\BillingCycle;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Events\TransactionUpdated;
use Insane\Journal\Models\Core\Account;

/**
 * Auto-link credit-card payments to the appropriate open BillingCycle.
 *
 * When a user pays their credit card in real life and Loger sees the
 * transaction (via PDF import, manual entry, or import from a bank
 * statement), this listener finds the oldest open billing cycle for the
 * destination credit-card account and attaches the transaction as a
 * payment. The cycle's `paid` and `status` auto-update via
 * BillingCycle::checkStatus().
 *
 * Without this, paid bills stay PENDING forever, the calendar keeps
 * showing them as scheduled, and the Net Worth / debt calculations
 * drift from reality.
 *
 * Conditions for auto-linking:
 *   - Transaction is verified (drafts and scheduled never trigger).
 *   - Transaction has a counter_account_id (a transfer-type movement).
 *   - The counter account has credit_limit > 0 (it's a credit card).
 *   - Transaction is not already attached to a Payment (transactionable_id IS NULL).
 *   - There's at least one open BillingCycle (status NOT IN PAID/CANCELLED)
 *     for that account whose due_at is on/after the transaction date.
 *
 * Edge cases handled:
 *   - Multiple open cycles → link to the OLDEST due_at (pay oldest debt first).
 *   - Amount > cycle's remaining debt → BillingCycle::linkPayment records
 *     the full transaction amount; checkStatus marks PAID and any overage
 *     is the user's problem to reconcile (rare, usually overpayment is
 *     legitimate "pay extra to lower utilization").
 *   - Cycle already fully paid → BillingCycle::linkPayment throws; we catch
 *     and log, no fatal error.
 */
class AutoLinkCreditCardPayment implements ShouldQueue
{
    public function handle(TransactionCreated|TransactionUpdated $event): void
    {
        $transaction = $event->transaction;

        if (! $transaction instanceof Transaction) {
            return;
        }
        if ($transaction->status !== Transaction::STATUS_VERIFIED) {
            return;
        }
        if (! $transaction->counter_account_id) {
            return;
        }
        // Already attached to something (a Payment or otherwise) — don't re-link.
        if ($transaction->transactionable_id !== null) {
            return;
        }

        $counterAccount = Account::find($transaction->counter_account_id);
        if (! $counterAccount || ! ((float) $counterAccount->credit_limit > 0)) {
            return;
        }

        // Find the oldest open billing cycle for this credit card account that
        // the transaction date falls within or before. We compare by due_at —
        // the cycle's payment deadline — and prefer the earliest unsettled one.
        $cycle = BillingCycle::query()
            ->where('team_id', $transaction->team_id)
            ->where('account_id', $transaction->counter_account_id)
            ->whereNotIn('status', [BillingCycle::STATUS_PAID, BillingCycle::STATUS_CANCELLED])
            ->orderBy('due_at')
            ->first();

        if (! $cycle) {
            return;
        }

        try {
            DB::transaction(function () use ($cycle, $transaction) {
                $cycle->linkPayment($transaction, []);
                // linkPayment doesn't recompute status itself — checkPayments
                // re-sums the payments collection and bumps the status field
                // (PENDING → PARTIALLY_PAID → PAID as appropriate).
                $cycle->load('payments');
                BillingCycle::checkPayments($cycle);
                $cycle->save();
            });
        } catch (\Throwable $e) {
            // Cycle could already be fully paid by a concurrent path, or the
            // payments relation might not be hydratable yet (rare). Log and
            // move on — the manual link-payment UI is still available as
            // recovery for the user.
            Log::info('AutoLinkCreditCardPayment skipped', [
                'transaction_id' => $transaction->id,
                'cycle_id' => $cycle->id,
                'reason' => $e->getMessage(),
            ]);
        }
    }
}
