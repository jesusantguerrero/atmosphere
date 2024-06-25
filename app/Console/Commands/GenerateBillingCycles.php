<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Domains\Loans\Actions\UpdateLatePayments;
use Tests\Feature\CreditCard\Helpers\CreditCardBase;
use App\Domains\Transaction\Services\CreditCardReportService;

class GenerateBillingCycles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bg:generate-billing-cycles {teamId} {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(CreditCardReportService $service)
    {
        $teamId = $this->argument('teamId');
        $date = $this->argument('date');

        $monthsWithTransactions = $this->getFirstTransaction($teamId);
        return $service->generateBillingCycles($teamId, $date ?? $monthsWithTransactions->date);
    }

    private function getFirstTransaction(int $teamId) {
        return DB::table('transaction_lines')
            ->where([
                "team_id" => $teamId
            ])
            ->selectRaw("date_format(transaction_lines.date, '%Y-%m') AS date")
            ->groupBy(DB::raw("date_format(transaction_lines.date, '%Y-%m')"))
            ->orderBy('date')
            ->first();
    }
}
