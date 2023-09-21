<?php

namespace App\Domains\Integration\Concerns;

use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Transaction\Models\Transaction;
use Spatie\LaravelData\Data;

class PlannedTransactionDTO extends Data
{
    public function __construct(
        public int $team_id,
        public int $user_id,
        public ?int $account_id,
        public ?int $counter_account_id,
        public ?int $payee_id,
        public string $date,
        public string $currency_code,
        public int $category_id,
        public string $description,
        public string $direction,
        public int $total,
        public mixed $items,
        public TransactionMetadata $metaData,
        public string $end_type,
        public string $frequency,
        public string $interval,
        public bool $repeat_at_end_of_month,
        public string $repeat_on_day_of_month,
        public string $resource_type_id,
        public string $status,
        public string $timezone_id
    ) {

    }

    public static function fromTarget(BudgetTarget $target, $date)
    {
        return self::from([
            'team_id' => $target->team_id,
            'user_id' => $target->user_id,
            'date' => $date,
            'currency_code' => 'DOP',
            'category_id' => $target->category_id,
            'description' => $target->name,
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => $target->amount,
            'items' => [],
            'metaData' => TransactionMetadata::from([
                'resource_id' => $target->id,
                'resource_origin' => 'budget_targets',
                'resource_type' => 'budget_targets',
            ]),
            'end_type' => 'NEVER',
            'frequency' => 'MONTHLY',
            'interval' => 1,
            'repeat_at_end_of_month' => false,
            'repeat_on_day_of_month' => '24',
            'resource_type_id' => 'MANUAL',
            'status' => 'planned',
            'timezone_id' => 'America/Santo_Domingo',
        ]);
    }
}
