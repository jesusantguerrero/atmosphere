<?php

namespace App\Domains\Transaction\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Payment;
use Insane\Journal\Models\Core\Transaction;
use Insane\Journal\Traits\IPayableDocument;

class BillingCycle extends Model implements IPayableDocument
{
    // use HasPaymentDocuments;

    const STATUS_PENDING = 'PENDING';

    const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';

    const STATUS_LATE = 'LATE';

    const STATUS_PAID = 'PAID';

    const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'team_id',
        'user_id',
        'account_id',
        'end_at',
        'due_at',
        'start_at',
        'debt',
        'paid',
        'subtotal',
        'discounts',
        'total',
        'status',
        'nudged_post_cut_at',
        'nudged_pre_due_at',
        'nudged_overdue_at',
    ];

    protected $creditCategory = 'credit_cards';

    protected $creditAccount = 'Credit Card Billings';

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($payable) {
            self::calculateTotal($payable);
            self::checkPayments($payable);
        });

        static::deleting(function ($invoice) {
            Payment::where('payable_id', $invoice->id)
                ->where('payable_type', self::class)->delete();
        });
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions()
    {
        return $this->hasMany(TransactionLine::class, 'account_id')
            ->join(DB::raw('accounts a'), 'a.id', 'account_id')
            ->where(fn ($q) => $q->whereRaw('(
                (transaction_lines.date >= ? AND transaction_lines.date < ? AND transaction_lines.type = -1)
                OR 
                (transaction_lines.date = ? AND transaction_lines.type = 1)
            )', [
                $this->start_at,
                $this->end_at,
                $this->end_at,
            ])
            );
    }

    // Transactionable config
    public function getTransactionItems()
    {
        return [];
    }

    public static function getCategoryName($payable): string
    {
        return 'credit_cards';
    }

    public function getTransactionDescription()
    {
        return 'Balance de credito';
    }

    public function getTransactionDirection(): string
    {
        return Transaction::DIRECTION_CREDIT;
    }

    public function getAccountId()
    {
        return $this->account_id;
    }

    public function getCounterAccountId(): int
    {
        return $this->client_account_id;
    }

    // payable config
    public function getStatusField(): string
    {
        return 'status';
    }

    public static function calculateTotal($payable)
    {
        // $payable->total = $payable->transactions()->sum('amount');
        $payable->debt = $payable->total - $payable->paid;
    }

    public static function checkStatus($payable): string
    {
        $total = (float) $payable->total;
        $paid = (float) $payable->paid;
        $debt = $total - $paid;

        $statusField = $payable->getStatusField();
        if (($payable->$statusField ?? null) === self::STATUS_CANCELLED) {
            return self::STATUS_CANCELLED;
        }

        if ($total > 0.00001 && $debt <= 0.00001) {
            return self::STATUS_PAID;
        }

        if ($paid > 0.00001) {
            return self::STATUS_PARTIALLY_PAID;
        }

        return self::STATUS_PENDING;
    }

    public function updateStatus()
    {
        self::checkPayments($this);
        $this->save();
        self::calculateTotal($this);
        self::checkStatus($this);
    }

    public function getConceptLine(): string
    {
        return '';
    }

    /**
     * Recompute paid / debt / status from the cycle's payment sources.
     *
     * Two sources contribute:
     *
     *   1) Payment rows in the `payments` morph relation — created by the
     *      AutoLinkCreditCardPayment listener on TransactionCreated and by
     *      the manual link-payments UI. Acts as an audit trail.
     *   2) Verified transactions targeting this card's account within the
     *      cycle window that DON'T yet have a Payment row attached
     *      (`transactionable_id IS NULL`). This catches:
     *        - historical transactions imported before the auto-link
     *          listener existed
     *        - any transaction the listener skipped (e.g. it was in DRAFT
     *          status at creation time and got verified later by a path
     *          that doesn't re-fire the listener)
     *      Without this fallback, those payments are invisible to the
     *      cycle and it stays PENDING even though the money clearly moved.
     */
    public static function checkPayments($payable)
    {
        if (! $payable) {
            return;
        }

        $totalPaid = self::computePaid($payable);
        $payable->paid = $totalPaid;
        $payable->debt = (float) $payable->total - $totalPaid;
        $statusField = $payable->getStatusField();
        $payable->$statusField = self::checkStatus($payable);
    }

    /**
     * Amount considered paid against $payable: its explicitly-linked Payment
     * rows plus its share of any verified card payments that aren't linked to
     * a Payment yet.
     *
     * Unlinked payments are allocated across the account's cycles oldest
     * statement first — mirroring AutoLinkCreditCardPayment's "oldest open
     * cycle" rule — and a payment can only settle a cycle whose cut date
     * (end_at) has already passed on the payment date. You pay a statement
     * after it closes, never the cycle still accumulating. Allocation is
     * capped at each cycle's remaining debt, so an arrears payment for a prior
     * statement never spills into (and prematurely settles) the open cycle.
     */
    protected static function computePaid($payable): float
    {
        if (! $payable->account_id || ! $payable->team_id) {
            return (float) $payable->payments()->sum('amount');
        }

        $cycles = self::query()
            ->where('team_id', $payable->team_id)
            ->where('account_id', $payable->account_id)
            ->orderBy('end_at')
            ->get(['id', 'end_at', 'total']);

        $linkedByCycle = Payment::query()
            ->where('payable_type', self::class)
            ->whereIn('payable_id', $cycles->pluck('id')->filter()->all())
            ->selectRaw('payable_id, sum(amount) as linked')
            ->groupBy('payable_id')
            ->pluck('linked', 'payable_id');

        $selfKey = $payable->id ?? '_self';
        $selfLinked = (float) ($payable->id ? ($linkedByCycle[$payable->id] ?? 0) : 0);

        $ledger = [];
        foreach ($cycles as $cycle) {
            if ($payable->id && $cycle->id === $payable->id) {
                continue;
            }
            $linked = (float) ($linkedByCycle[$cycle->id] ?? 0);
            $ledger[$cycle->id] = [
                'end_at' => substr((string) $cycle->end_at, 0, 10),
                'remaining' => max(0.0, (float) $cycle->total - $linked),
                'allocated' => 0.0,
            ];
        }
        $ledger[$selfKey] = [
            'end_at' => substr((string) $payable->end_at, 0, 10),
            'remaining' => max(0.0, (float) $payable->total - $selfLinked),
            'allocated' => 0.0,
        ];

        uasort($ledger, fn ($a, $b) => $a['end_at'] <=> $b['end_at']);

        $payments = Transaction::query()
            ->where('team_id', $payable->team_id)
            ->where('counter_account_id', $payable->account_id)
            ->where('status', 'verified')
            ->whereNull('transactionable_id')
            ->orderBy('date')
            ->get(['date', 'total']);

        foreach ($payments as $payment) {
            $amount = (float) $payment->total;
            $payDate = substr((string) $payment->date, 0, 10);
            foreach ($ledger as $key => $row) {
                if ($amount <= 0.00001) {
                    break;
                }
                if ($row['end_at'] >= $payDate) {
                    continue;
                }
                $room = $row['remaining'] - $row['allocated'];
                if ($room <= 0.00001) {
                    continue;
                }
                $give = min($room, $amount);
                $ledger[$key]['allocated'] += $give;
                $amount -= $give;
            }
        }

        return $selfLinked + ($ledger[$selfKey]['allocated'] ?? 0.0);
    }

    public function linkPayment(Transaction $transaction, $formData)
    {
        $paid = $this->payments;
        if ($paid >= $this->total) {
            throw new Exception("The document {$this->concept} is already paid");
        }

        $payment = $this->payments()->create([
            'amount' => $transaction->total,
            'payment_date' => $transaction->date,
            'concept' => $transaction->description,
            'account_id' => $transaction->counter_account_id,
            'category_id' => null,
            'user_id' => $transaction->user_id,
            'team_id' => $transaction->team_id,
            'client_id' => $transaction->user_id,
            'currency_code' => $transaction->currency_code,
            'currency_rate' => $transaction->currency_rate,
            'status' => 'verified',
            'transaction_id' => $transaction->id,
        ]);

        $transaction->update([
            'transactionable_type' => Payment::class,
            'transactionable_id' => $payment->id,
        ]);

        $this->save();

        return $payment;
    }

    public function createPayment($formData)
    {
        $paid = $this->payments->sum('amount');
        if ($paid >= $this->total) {
            throw new Exception('This invoice is already paid');
        }

        $debt = $this->total - $paid;

        $formData['amount'] = $formData['amount'] > $debt ? $debt : $formData['amount'];
        $payment = $this->payments()->create([
            ...$formData,
            'user_id' => $formData['user_id'] ?? $this->user_id,
            'team_id' => $formData['team_id'] ?? $this->team_id,
            'client_id' => $formData['client_id'] ?? $this->user_id,
        ]);

        $this->save();

        return $payment;
    }

    public function createPaymentTransaction(Payment $payment)
    {
        $direction = Transaction::DIRECTION_CREDIT;
        $counterAccountId = $this->account_id;

        return [
            'team_id' => $payment->team_id,
            'user_id' => $payment->user_id,
            'date' => $payment->payment_date,
            'description' => $payment->concept,
            'direction' => $direction,
            'total' => $payment->amount,
            'account_id' => $payment->account_id,
            'counter_account_id' => $counterAccountId,
            'items' => [],
        ];
    }
}
