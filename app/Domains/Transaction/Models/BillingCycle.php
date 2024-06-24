<?php

namespace App\Domains\Transaction\Models;

use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Core\Transaction;
use Insane\Journal\Traits\HasPaymentDocuments;

class BillingCycle extends Model
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
        'due',
        'subtotal',
        'discounts',
        'total'
    ];

    protected $creditCategory = 'credit_cards';
    protected $creditAccount = 'Credit Card Billings';

    public function transactions() {
        return $this->hasMany(TransactionLine::class, 'account_id')
        ->whereBetween("date", [$this->from, $this->until]);
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
        return 'payment_status';
    }

    public static function calculateTotal($payable) {
        $payable->total = $payable->transactions()->sum('amount');
        $payable->due = $payable->total - $payable->paid;
    }

    public static function checkStatus($payable) {
        $debt = $payable->total - $payable->paid;
        if ($debt <= 0) {
            $status = self::STATUS_PAID;
        } elseif ($debt > 0 && $debt < $payable->amount) {
            $status = self::STATUS_PARTIALLY_PAID;
        } elseif ($debt && $payable->hasLateInstallments()) {
            $status = self::STATUS_LATE;
        } elseif ($debt && !$payable->cancelled_at) {
            $status = self::STATUS_PENDING;
        } elseif ($payable->cancelled_at) {
            $status = self::STATUS_CANCELLED;
        } else {
            $status = $payable->status;
        }
        $payable->status = $status;
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
}
