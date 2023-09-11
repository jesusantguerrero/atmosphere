<?php
namespace App\Domains\Budget\Data;


enum FixedCategories: string {
    case READY_TO_ASSIGN = "Ready to Assign";
    case INFLOW = "Inflow";
}
