<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        $amount = $monthBudget->budgeted - $currentBalance;
        $originalFrom = $form['source_category_id'] ?? Category::findOrCreateByName($session, Category::READY_TO_ASSIGN);
        $originalDestination = $monthBudget->category_id;
        if ($amount != 0) {
            $formData = array_merge($session, [
                'source_category_id' => $amount > 0 ? $originalFrom : $originalDestination,
                'destination_category_id' => $amount > 0 ? $originalDestination : $originalFrom,
                'amount' => $amount,
                'date' => date('Y-m-d')
            ]);
            return self::create($formData);
        }

    }

    public static function getBalanceOfCategory($categoryId) {
        return self::where("source_category_id", $categoryId)
        ->orWhere("destination_category_id", $categoryId)
        ->sum("amount");
    }
}
