<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Domains\Transaction\Services\CreditCardReportService;

class GenerateBillingCycles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bg:generate-billing-cycles {date?} {--A|all}';

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
        $date = $this->argument("date");
        $shouldGenerateAll = $this->option("all");
        $teams = Team::with(["timezone"])->without(["settings"])->get();
        $thisMonth = now();
        foreach ($teams as $team) {
            $timezone = $team["timezone"] ?? null;
            if ($timezone) {
                $thisMonth->setTimezone($timezone["value"]);
            };

            if ($date || !$shouldGenerateAll) {
                $service->generateBillingCycles($team->id, $date ?? $thisMonth->format("Y-m"));
            } else {
                $monthsWithTransactions = $this->getFirstTransaction($team->id);
                $service->generateBillingCycles($team->id, $monthsWithTransactions->date);
            }
        }
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
