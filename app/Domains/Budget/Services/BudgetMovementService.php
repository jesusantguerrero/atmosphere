<?php

namespace App\Domains\Budget\Services;

use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Data\BudgetMovementData;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category;

class BudgetMovementService
{
    const MODE_ADD = 'add';

    const MODE_SUBTRACT = 'subtract';

    public function __construct(public BudgetCategoryService $budgetService)
    {
    }

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
        ], ['budgeted' => $mode ? DB::raw("budgeted $modeSign $amount") : $amount]);
    }

    public function registerMovement(BudgetMovementData $data)
    {
        $session = [
            'team_id' => $data->team_id,
            'user_id' => $data->user_id,
        ];

        $sourceId = $data->source_category_id ?? Category::findOrCreateByName($session, BudgetReservedNames::READY_TO_ASSIGN->value);
        $destinationId = $data->destination_category_id;

        $sourceCategoryBalance = self::getBalanceOfCategory($sourceId, $data->date); // 2000
        $amount = $data->amount > $sourceCategoryBalance->available ? $sourceCategoryBalance->available : $data->amount; // 2096 > 2000 ? 2000 : 2096

        $formData = array_merge($session, [
            'source_category_id' => $sourceId,
            'destination_category_id' => $destinationId,
            'amount' => $amount,
            'date' => $data->date,
        ]);
        DB::beginTransaction();

        $savedMovement = BudgetMovement::create($formData);

        if ($savedMovement->source_category_id) {
            $this->updateBalances($destinationId, $savedMovement, $savedMovement->date, $amount, self::MODE_ADD);
            $this->updateBalances($sourceId, $savedMovement, $savedMovement->date, $amount, self::MODE_SUBTRACT);
        }
        DB::commit();
    }

    public function registerAssignment(BudgetAssignData $data)
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
        if ($savedMovement->source_category_id) {
            $this->updateBalances($destinationId, $savedMovement, $savedMovement->date, $amount);
            $this->updateBalances($sourceId, $savedMovement, $savedMovement->date, $amount, self::MODE_SUBTRACT);
        }
        DB::commit();
    }

    public function getBalanceOfCategory($categoryId, string $month)
    {
        return (object) $this->budgetService->getBudgetInfo(Category::find($categoryId), $month);
    }
    // public function getBalanceOfCategory($categoryId) {
    //     return DB::table('budget_movements')
    //     ->selectRaw("SUM(CASE WHEN source_category_id=$categoryId THEN amount * -1 ELSE amount * 1 END) as balance")
    //     ->where("source_category_id", $categoryId)
    //     ->orWhere("destination_category_id", $categoryId)
    //     ->first()->balance;
    // }
}
