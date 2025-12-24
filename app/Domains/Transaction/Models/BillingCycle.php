<?php

namespace App\Domains\Transaction\Models;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Core\Payment;
use Insane\Journal\Models\Core\Transaction;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Traits\IPayableDocument;

class BillingCycle extends Model implements IPayableDocument
{
    // use HasPaymentDocuments;

    const STATUS_PENDING = 'PENDING';
    const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
    const STATUS_LATE =  'LATE';
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
        'status'
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

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function transactions() {
        return $this->hasMany(TransactionLine::class, 'account_id')
        ->join(DB::raw('accounts a'), 'a.id', 'account_id')
        ->where(fn ($q) =>
            $q->whereRaw("(
                (transaction_lines.date >= ? AND transaction_lines.date < ? AND transaction_lines.type = -1)
                OR 
                (transaction_lines.date = ? AND transaction_lines.type = 1)
            )", [
                $this->start_at,
                $this->end_at, 
                $this->end_at
            ])
        );
    }

    // Transactionable config
    public function getTransactionItems() {
        return [];
    }

    public static function getCategoryName($payable): string {
        return "credit_cards";
    }

    public function getTransactionDescription() {
        return "Balance de credito";
    }

    public function getTransactionDirection(): string {
        return Transaction::DIRECTION_CREDIT;
    }

    public function getAccountId() {
        return $this->account_id;
    }

    public function getCounterAccountId(): int {
        return $this->client_account_id;
    }

     // payable config
     public function getStatusField(): string {
        return 'status';
    }

    public static function calculateTotal($payable) {
        // $payable->total = $payable->transactions()->sum('amount');
        $payable->debt = $payable->total - $payable->paid;
    }

    public static function checkStatus($payable) {
        $debt = $payable->total - $payable->paid;
        if ($debt <= 0) {
            $status = self::STATUS_PAID;
        } elseif ($debt > 0 && $debt < $payable->amount) {
            $status = self::STATUS_PARTIALLY_PAID;
        } elseif ($debt && $payable->end_at < $payable->payments?->first()?->payment_date) {
            $status = self::STATUS_LATE;
        } elseif ($debt && !$payable->cancelled_at) {
            $status = self::STATUS_PENDING;
        } elseif ($payable->cancelled_at) {
            $status = self::STATUS_CANCELLED;
        } else {
            $status = $payable->status;
        }
       return $status;
      }

    public function updateStatus() {
        self::checkPayments($this);
        $this->save();
        self::calculateTotal($this);
        self::checkStatus($this);
    }

    public function getConceptLine(): string {
        return "";
    }

    public static function checkPayments($payable)
    {
        if ($payable && $payable->payments) {
            $totalPaid = $payable->payments()->sum('amount');
            $payable->paid = $totalPaid;
            $payable->debt = $payable->total - $totalPaid ;
            $statusField = $payable->getStatusField();
            $payable->$statusField = self::checkStatus($payable);
        }
    }

    public function linkPayment(Transaction $transaction, $formData) {
        $paid = $this->payments;
        if ($paid >= $this->total) {
          throw new Exception("The document {$this->concept} is already paid");
        }

        $payment = $this->payments()->create([
            "amount" => $transaction->total,
            "payment_date" => $transaction->date,
            "concept" => $transaction->description,
            "account_id" => $transaction->counter_account_id,
            "category_id" => null,
            'user_id' => $transaction->user_id,
            'team_id' => $transaction->team_id,
            'client_id' => $transaction->user_id,
            'currency_code' => $transaction->currency_code,
            'currency_rate' => $transaction->currency_rate,
            'status' => 'verified',
            'transaction_id' => $transaction->id
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
            throw new Exception("This invoice is already paid");
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

    public function createPaymentTransaction(Payment $payment) {
        $direction = Transaction::DIRECTION_CREDIT;
        $counterAccountId = $this->account_id;

        return [
            "team_id" => $payment->team_id,
            "user_id" => $payment->user_id,
            "date" => $payment->payment_date,
            "description" => $payment->concept,
            "direction" => $direction,
            "total" => $payment->amount,
            "account_id" => $payment->account_id,
            "counter_account_id" => $counterAccountId,
            "items" => []
        ];
    }
}
