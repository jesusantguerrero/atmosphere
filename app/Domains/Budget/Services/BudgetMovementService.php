<?php

namespace App\Domains\Budget\Services;

use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Data\BudgetMovementData;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use App\Events\BudgetAssigned;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category;

class BudgetMovementService
{
    const MODE_ADD = 'add';

    const MODE_SUBTRACT = 'subtract';

    public function __construct(public BudgetCategoryService $budgetService, private BudgetRolloverService $budgetRolloverService) {}

    /**
     * Based on the direction of the movement for each account add or subtract the amount
     */
    public function updateBalances(int $categoryId, BudgetMovement $movement, string $date, float $amount, $mode = null)
    {
        $month = substr($date, 0, 7).'-01';
        $modeSign = $mode == self::MODE_ADD ? '+' : '-';

        BudgetMonth::updateOrCreate([
            'user_id' => $movement->user_id,
            'team_id' => $movement->team_id,
            'category_id' => $categoryId,
            'month' => $month,
            'name' => $month,
        ], [
            'budgeted' => $mode ? DB::raw("budgeted $modeSign $amount") : $amount,
        ]);
    }

    public function registerMovement(BudgetMovementData $data, $quietly = false, $fromReadyToAssign = false)
    {
        $session = [
            'team_id' => $data->team_id,
            'user_id' => $data->user_id,
        ];

        $sourceId = $data->source_category_id ?? Category::findOrCreateByName($session, BudgetReservedNames::READY_TO_ASSIGN->value);
        $destinationId = $data->destination_category_id;
        $destinationBalance = self::getBalanceOfCategory($destinationId, $data->date);

        $sourceCategoryBalance = self::getBalanceOfCategory($sourceId, $data->date); // 2000
        $amount = $data->amount > $sourceCategoryBalance->available && ! $fromReadyToAssign ? $sourceCategoryBalance->available : $data->amount; // 2096 > 2000 ? 2000 : 2096

        $isPositiveTransaction = $amount > 0;

        $formData = array_merge($session, [
            'source_category_id' => $isPositiveTransaction ? $sourceId : $destinationId,
            'destination_category_id' => $isPositiveTransaction ? $destinationId : $sourceId,
            'amount' => $amount,
            'date' => $data->date,
        ]);
        DB::beginTransaction();

        $savedMovement = BudgetMovement::create($formData);

        if ($savedMovement->source_category_id && ! $quietly) {
            $this->updateBalances($destinationId, $savedMovement, $savedMovement->date, $amount, self::MODE_ADD);
            $this->updateBalances($sourceId, $savedMovement, $savedMovement->date, $amount, self::MODE_SUBTRACT);
        }
        DB::commit();
        $this->budgetRolloverService->startFrom($data->team_id, substr($data->date, 0, 7), 1);
        if (! now()->isSameMonth(Carbon::createFromFormat('Y-m-d', $data->date))) {
            BudgetAssigned::dispatch($data, $formData);
        }
    }

    public function registerAssignment(BudgetAssignData $data, $quietly = false)
    {
        DB::beginTransaction();
        $session = [
            'team_id' => $data->team_id,
            'user_id' => $data->user_id,
        ];

        $sourceId = Category::findOrCreateByName($session, BudgetReservedNames::READY_TO_ASSIGN->value);
        $destinationId = $data->category_id;

        $amount = $data->amount;

        $formData = array_merge($session, [
            'source_category_id' => $sourceId,
            'destination_category_id' => $destinationId,
            'amount' => $amount,
            'date' => $data->date,
        ]);

        $savedMovement = BudgetMovement::create($formData);
        if ($savedMovement->source_category_id && ! $quietly) {
            $this->updateBalances($destinationId, $savedMovement, $savedMovement->date, $amount);
            $this->updateBalances($sourceId, $savedMovement, $savedMovement->date, $amount, self::MODE_SUBTRACT);
        }
        DB::commit();
        $this->budgetRolloverService->startFrom($data->team_id, substr($data->date, 0, 7), 1);
        if (! now()->isSameMonth(Carbon::createFromFormat('Y-m-d', $data->date))) {
            BudgetAssigned::dispatch($data, $formData);
        }
    }

    public function getBalanceOfCategory($categoryId, string $month)
    {
        return (object) $this->budgetService->getBudgetInfo(Category::find($categoryId), $month);
    }

    /**
     * Copy each category's `budgeted` from the previous month to a target month using the same
     * assignment primitive the per-row input uses, so rollover recomputation stays consistent.
     *
     * @return array{copied:int, skipped:int}
     */
    public function copyFromPrevious(int $teamId, int $userId, string $targetMonth, bool $overwrite = false): array
    {
        $previousMonth = Carbon::createFromFormat('Y-m-d', $targetMonth)
            ->subMonthNoOverflow()
            ->startOfMonth()
            ->format('Y-m-d');

        $reserved = array_map(fn (BudgetReservedNames $n) => $n->value, BudgetReservedNames::cases());

        $previousRows = BudgetMonth::query()
            ->where('budget_months.team_id', $teamId)
            ->where('budget_months.month', $previousMonth)
            ->where('budget_months.budgeted', '>', 0)
            ->join('categories', 'categories.id', 'budget_months.category_id')
            ->whereNotIn('categories.name', $reserved)
            ->select('budget_months.category_id', 'budget_months.budgeted')
            ->get();

        $copied = 0;
        $skipped = 0;

        foreach ($previousRows as $row) {
            $existing = BudgetMonth::where([
                'team_id' => $teamId,
                'category_id' => $row->category_id,
                'month' => $targetMonth,
            ])->first();

            $current = (float) ($existing?->budgeted ?? 0);
            $target = (float) $row->budgeted;

            if ($current > 0 && ! $overwrite) {
                $skipped++;

                continue;
            }

            if (abs($current - $target) < 0.01) {
                $skipped++;

                continue;
            }

            $this->registerAssignment(new BudgetAssignData(
                $teamId,
                $userId,
                $targetMonth,
                $row->category_id,
                $target,
            ));

            $copied++;
        }

        return ['copied' => $copied, 'skipped' => $skipped];
    }

    /**
     * Reverse a single movement: subtract the amount from the destination,
     * add it back to the source, delete the movement row, then re-trigger
     * rollover for the affected month so dependent months stay consistent.
     *
     * Treats the movement amount as the magnitude that was applied at registration
     * time (registerMovement clamps amount to source available, so the stored
     * `amount` is what actually moved — the right value to invert).
     */
    public function revertMovement(BudgetMovement $movement): void
    {
        DB::transaction(function () use ($movement) {
            $amount = (float) $movement->amount;

            $this->updateBalances(
                $movement->destination_category_id,
                $movement,
                $movement->date,
                $amount,
                self::MODE_SUBTRACT,
            );
            $this->updateBalances(
                $movement->source_category_id,
                $movement,
                $movement->date,
                $amount,
                self::MODE_ADD,
            );

            $movement->delete();
        });

        $this->budgetRolloverService->startFrom(
            $movement->team_id,
            substr($movement->date, 0, 7),
            1,
        );
    }

    /**
     * Distribute an amount from one source category across multiple destinations in a single transaction.
     *
     * @param  array<int, array{destination_category_id:int, amount:float}>  $splits
     */
    public function registerSplit(int $teamId, int $userId, ?int $sourceCategoryId, string $date, array $splits): void
    {
        DB::transaction(function () use ($teamId, $userId, $sourceCategoryId, $date, $splits) {
            foreach ($splits as $split) {
                $amount = (float) ($split['amount'] ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                $this->registerMovement(BudgetMovementData::from([
                    'id' => null,
                    'team_id' => $teamId,
                    'user_id' => $userId,
                    'source_category_id' => $sourceCategoryId,
                    'destination_category_id' => (int) $split['destination_category_id'],
                    'type' => 'movement',
                    'date' => $date,
                    'amount' => $amount,
                ]), false, true);
            }
        });
    }
}
