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

    public static function checkStatus($payable)
    {
        $debt = $payable->total - $payable->paid;
        if ($debt <= 0) {
            $status = self::STATUS_PAID;
        } elseif ($debt > 0 && $debt < $payable->amount) {
            $status = self::STATUS_PARTIALLY_PAID;
        } elseif ($debt && $payable->end_at < $payable->payments?->first()?->payment_date) {
            $status = self::STATUS_LATE;
        } elseif ($debt && ! $payable->cancelled_at) {
            $status = self::STATUS_PENDING;
        } elseif ($payable->cancelled_at) {
            $status = self::STATUS_CANCELLED;
        } else {
            $status = $payable->status;
        }

        return $status;
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

        $linkedAmount = (float) $payable->payments()->sum('amount');

        $unlinkedAmount = 0.0;
        if ($payable->account_id && $payable->start_at && $payable->due_at) {
            $unlinkedAmount = (float) Transaction::query()
                ->where('team_id', $payable->team_id)
                ->where('counter_account_id', $payable->account_id)
                ->where('status', 'verified')
                ->whereBetween('date', [$payable->start_at, $payable->due_at])
                ->whereNull('transactionable_id')
                ->sum('total');
        }

        $totalPaid = $linkedAmount + $unlinkedAmount;
        $payable->paid = $totalPaid;
        $payable->debt = (float) $payable->total - $totalPaid;
        $statusField = $payable->getStatusField();
        $payable->$statusField = self::checkStatus($payable);
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
            'status' =>