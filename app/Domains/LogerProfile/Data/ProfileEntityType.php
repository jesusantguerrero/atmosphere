<?php
namespace App\Domains\LogerProfile\Data;

use App\Domains\AppCore\Models\Category;
use App\Domains\Housing\Models\OccurrenceCheck;
use App\Models\Account;
use Modules\Plan\Entities\Plan;

enum ProfileEntityType: string {
    case Category = "category";
    case OccurrenceCheck = "occurrence_check";
    case Plan = "plan";
    case Account = "account";
    case Menu = "menu";
    case Schedule = "schedule";


    public function getClassName() {
        return match($this->value) {
            self::Category => Category::class,
            self::OccurrenceCheck => OccurrenceCheck::class,
            self::Plan => Plan::class,
            self::Account => Account::class,
            self::Schedule => Planner::class,
        };

    }
}
