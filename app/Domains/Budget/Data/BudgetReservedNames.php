<?php
namespace App\Domains\Budget\Data;


enum BudgetReservedNames: string {
    case READY_TO_ASSIGN = "Ready to Assign";
    case INFLOW = "Inflow";
    case CREDIT_CARD_PAYMENTS = "Credit Card Payments";
}
