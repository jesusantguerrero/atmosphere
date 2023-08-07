<?php
namespace App\Domains\LogerProfile\Data;

use App\Domains\AppCore\Models\Category;
use App\Domains\Housing\Models\OccurrenceCheck;
use App\Models\Account;
use Exception;
use Modules\Plan\Entities\Plan;

enum ProfileEntityType: string {
    case Category = "category";
    case OccurrenceCheck = "occurrence_check";
    case Plan = "plan";
    case Account = "account";
    case Menu = "menu";
    case Schedule = "schedule";


    public function getClassName() {
        return match($this->name) {
            self::Category->name => Category::class,
            self::OccurrenceCheck->name => OccurrenceCheck::class,
            self::Plan->name => Plan::class,
            self::Account->name => Account::class,
            self::Schedule->name => Planner::class,
        };
    }

    public static function getClass($name): string
    {
        try {
            return ProfileEntityType::from($name)->getClassName();
        } catch (Exception $e) {
            return "";
        }
    }
}
