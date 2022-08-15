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
        $session = [
            'team_id' => $monthBudget->team_id,
            'user_id' => $monthBudget->user_id,
        ];

        $currentBalance = self::getBalanceOfCategory($monthBudget->category_id);
        $originalFrom = $form['source_category_id'] ?? Category::findOrCreateByName($session, Category::READY_TO_ASSIGN);
        $originalDestination = $monthBudget->category_id;

        if (isset($form['type']) && $form['type'] == 'movement') {
            $amount = $form['budgeted'];
            $source = $form['source_category_id'];
            $destination = $form['destination_category_id'];
        } else {
            $amount = $monthBudget->budgeted - $currentBalance;
            $source = $amount > 0 ? $originalFrom : $originalDestination;
            $destination = $amount > 0 ? $originalDestination : $originalFrom;
        }

        $formData = array_merge($session, [
            'source_category_id' => $source,
            'destination_category_id' => $destination,
            'amount' => $amount,
            'date' => date('Y-m-d')
        ]);

        $savedMovement = self::create($formData);
        $month = substr($savedMovement->date, 0, 7)."-01";
        MonthBudget::updateOrCreate([
            'category_id' => $savedMovement->source_category_id,
            'user_id' => $savedMovement->user_id,
            'team_id' => $savedMovement->team_id,
            'month' => $month,
            'name' => $month,
        ], ['budgeted' => DB::raw("budgeted + -$amount")]);
    }

    public static function getBalanceOfCategory($categoryId) {
        return self::where("source_category_id", $categoryId)
        ->orWhere("destination_category_id", $categoryId)
        ->sum("amount");
    }
}
