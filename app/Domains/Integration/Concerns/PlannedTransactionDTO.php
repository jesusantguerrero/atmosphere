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
        public string $currency_code = 'USD',
        public ?int $category_id = null,
        public string $description = '',
        public string $direction = 'WITHDRAW',
        public int $total = 0,
        public array $items = [],
        public ?TransactionMetaData $metaData = null,
        public string $end_type = 'NEVER',
        public string $frequency = 'MONTHLY',
        public int $interval = 1,
        public bool $repeat_at_end_of_month = false,
        public ?string $repeat_on_day_of_month = null,
        public string $resource_type_id = 'MANUAL',
        public string $status = 'planned',
        public string $timezone_id = 'America/Santo_Domingo'
    ) {}

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
            'metaData' => new TransactionMetaData(
                $target->id,
                'budget_targets',
                'budget_targets'
            ),
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
