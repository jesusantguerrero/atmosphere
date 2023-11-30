<?php

namespace App\Domains\Budget\Services;

use App\Events\BudgetAssigned;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Budget\Data\BudgetMovementData;
use App\Domains\Budget\Data\BudgetReservedNames;

class BudgetMovementService
{
    const MODE_ADD = 'add';

    const MODE_SUBTRACT = 'subtract';

    public function __construct(public BudgetCategoryService $budgetService) {}

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
            'budgeted' => $mode ? DB::raw("budgeted $modeSign $amount") : $amount
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
        $amount = $data->amount > $sourceCategoryBalance->available && !$fromReadyToAssign ? $sourceCategoryBalance->available : $data->amount; // 2096 > 2000 ? 2000 : 2096

        $isPositiveTransaction = $amount > 0;

        $formData = array_merge($session, [
            'source_category_id' => $isPositiveTransaction ? $sourceId : $destinationId,
            'destination_category_id' => $isPositiveTransaction ? $destinationId : $sourceId,
            'amount' => $destinationBalance->available - $amount,
            'date' => $data->date,
        ]);
        DB::beginTransaction();

        $savedMovement = BudgetMovement::create($formData);

        if ($savedMovement->source_category_id && !$quietly)  {
            $this->updateBalances($destinationId, $savedMovement, $savedMovement->date, $amount, self::MODE_ADD);
            $this->updateBalances($sourceId, $savedMovement, $savedMovement->date, $amount, self::MODE_SUBTRACT);
        }
        DB::commit();
        BudgetAssigned::dispatch($data, $formData);
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
        if ($savedMovement->source_category_id && !$quietly) {
            $this->updateBalances($destinationId, $savedMovement, $savedMovement->date, $amount);
            $this->updateBalances($sourceId, $savedMovement, $savedMovement->date, $amount, self::MODE_SUBTRACT);
        }
        DB::commit();
        BudgetAssigned::dispatch($data, $formData);
    }

    public function getBalanceOfCategory($categoryId, string $month)
    {
        return (object) $this->budgetService->getBudgetInfo(Category::find($categoryId), $month);
    }
}
