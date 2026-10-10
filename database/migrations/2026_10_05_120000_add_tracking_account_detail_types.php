<?php

use Illuminate\Database\Migrations\Migration;
use Insane\Journal\Models\Core\AccountDetailType;

/**
 * Tracking (off-budget) account types, YNAB-style: a Property/Asset held at a
 * value and a Loan/Liability. They count toward net worth (see
 * TransactionService::getNetWorth) but stay out of the budget / Ready to Assign
 * (see App\Listeners\CreateStartingBalance). balance_type drives the sign:
 * property = debit (asset), loan = credit (liability).
 */
return new class extends Migration
{
    private array $types = [
        [
            'name' => 'property',
            'label' => 'Property / Asset',
            'description' => 'Track a property or other asset held at a value, outside the budget. Counts toward net worth; revalue with a manual adjustment.',
            'config' => ['balance_type' => 'debit', 'category_id' => null],
        ],
        [
            'name' => 'loan',
            'label' => 'Loan / Liability',
            'description' => 'Track a loan or other liability, outside the budget. Counts as debt toward net worth; pay it down with a transfer.',
            'config' => ['balance_type' => 'credit', 'category_id' => null],
        ],
    ];

    public function up(): void
    {
        foreach ($this->types as $type) {
            AccountDetailType::firstOrCreate(
                ['team_id' => 0, 'name' => $type['name']],
                [
                    'label' => $type['label'],
                    'description' => $type['description'],
                    'config' => $type['config'],
                ]
            );
        }
    }

    public function down(): void
    {
        AccountDetailType::where('team_id', 0)
            ->whereIn('name', ['property', 'loan'])
            ->delete();
    }
};
