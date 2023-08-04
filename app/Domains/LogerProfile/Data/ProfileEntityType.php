<?php
namespace App\Domains\LogerProfile\Data;


enum ProfileEntityType: string {
    case Category = "category";
    case OccurrenceCheck = "occurrence_check";
    case Plan = "plan";
    case Account = "account";
    case Menu = "menu";
    case Schedule = "schedule";
}
