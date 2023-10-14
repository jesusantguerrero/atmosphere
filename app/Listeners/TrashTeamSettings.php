<?php

namespace App\Listeners;

use App\Domains\AppCore\Models\Category;
use App\Domains\AppCore\Models\Label;
use App\Domains\AppCore\Models\Planner;
use App\Domains\Budget\Models\Budget;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Models\LogerProfileEntity;
use App\Domains\Meal\Models\Ingredient;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealMenu;
use App\Domains\Meal\Models\MealPlan;
use App\Domains\Meal\Models\MealType;
use App\Domains\Meal\Models\Product;
use App\Domains\Transaction\Models\LinkedTransaction;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Payment;
use Insane\Journal\Models\Core\PaymentDocument;
use Laravel\Jetstream\Events\TeamDeleted;

class TrashTeamSettings
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TeamDeleted $event)
    {
        $team = $event->team;

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Journal
        Transaction::where(['team_id' => $team->id])->delete();
        TransactionLine::where(['team_id' => $team->id])->delete();
        LinkedTransaction::where(['team_id' => $team->id])->delete();
        Payment::where(['team_id' => $team->id])->delete();
        PaymentDocument::where(['team_id' => $team->id])->delete();

        // core
        Account::where(['team_id' => $team->id])->delete();
        Category::where(['team_id' => $team->id])->delete();
        Label::where(['team_id' => $team->id])->delete();
        Planner::where(['team_id' => $team->id])->delete();

        //  Budget
        Budget::where(['team_id' => $team->id])->delete();
        BudgetMonth::where(['team_id' => $team->id])->delete();
        BudgetMovement::where(['team_id' => $team->id])->delete();
        BudgetTarget::where(['team_id' => $team->id])->delete();

        // housing
        Occurrence::where(['team_id' => $team->id])->delete();

        //  Loger profile
        LogerProfile::where(['team_id' => $team->id])->delete();
        LogerProfileEntity::where(['team_id' => $team->id])->delete();

        // meal
        Ingredient::where(['team_id' => $team->id])->delete();
        // MealMenu::where(["team_id" => $team->id])->delete();
        Meal::where(['team_id' => $team->id])->delete();
        MealPlan::where(['team_id' => $team->id])->delete();
        MealType::where(['team_id' => $team->id])->delete();
        Product::where(['team_id' => $team->id])->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
