<?php

namespace App\Domains\Budget\Services;

use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Integration\Concerns\PlannedTransactionDTO;
use App\Domains\Transaction\Services\PlannedTransactionService;

class BudgetTargetTransactionService {
    public function __construct(private PlannedTransactionService $plannedService)
    {

    }

    public function createPlannedTransactions(int $teamId) {
        $targets = BudgetTarget::where([
            "team_id" => $teamId
        ])
        ->whereNotNull('frequency_month_date')
        ->get();

        $months = [now()];

        foreach ($months as $month) {
            foreach ($targets as $target) {
                $this->buildPlanned($target, $month->format('Y-m'));
            }
        }
    }

    private function buildPlanned(BudgetTarget $target, $month) {
        $date = $month . "-" . $target->frequency_month_date;
        $data = PlannedTransactionDTO::fromTarget($target, $date);
        $this->plannedService->add($data);
    }
}


