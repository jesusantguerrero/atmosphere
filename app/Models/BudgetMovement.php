<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BudgetMovement extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id', 'source_category_id', 'destination_category_id', 'amount', 'date'];

    public static function registerMovement($monthBudget, $form) {
        DB::beginTransaction();
        $session = [
            'team_id' => $monthBudget->team_id,
            'user_id' => $monthBudget->user_id,
        ];

        // 2
        $sourceId = $form['source_category_id'] ?? Category::findOrCreateByName($session, Category::READY_TO_ASSIGN);
        // 4
        $destinationId = $monthBudget->category_id;
        // 0
        $categoryBalance = self::getBalanceOfCategory($destinationId);

        if (isset($form['type']) && $form['type'] == 'movement') {
            $amount = $form['budgeted'];
            $sourceId = $form['source_category_id'];
            $destinationId = $form['destination_category_id'];
        } else {
            $amount = $form['budgeted'] - $categoryBalance;
        }

        $formData = array_merge($session, [
            'source_category_id' => $sourceId,
            'destination_category_id' => $destinationId,
            'amount' => $amount,
            'date' => date('Y-m-d')
        ]);

        $savedMovement = self::create($formData);
        if ($savedMovement->source_category_id) {
            $month = substr($savedMovement->date, 0, 7)."-01";
            BudgetMonth::updateOrCreate([
                'category_id' => $savedMovement->source_category_id,
                'user_id' => $savedMovement->user_id,
                'team_id' => $savedMovement->team_id,
                'month' => $month,
                'name' => $month,
            ], ['budgeted' => DB::raw("budgeted + -$amount")]);
        }
        DB::commit();
    }

    public static function getBalanceOfCategory($categoryId) {
        return self::where("source_category_id", $categoryId)
        ->orWhere("destination_category_id", $categoryId)
        ->sum("amount");
    }
}
