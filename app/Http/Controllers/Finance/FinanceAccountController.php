<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Transaction\Services\ReportService;
use App\Models\Setting;
use Freesgen\Atmosphere\Http\InertiaController;
use Freesgen\Atmosphere\Http\Querify;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Models\Core\Account;

class FinanceAccountController extends InertiaController {
    use Querify;
    const DateFormat = 'Y-m-d';
    private $reportService;

    public function __construct(Account $account, ReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->model = $account;
        $this->templates = [
            'index' => 'Finance/Account',
            'show' => 'Finance/Account'
        ];
        $this->searchable = ["id", "date", 'concent'];
        $this->includes = [
            'transactions'
        ];
        $this->appends = [];
    }

    public function show(Account $account) {
        $queryParams = request()->query();
        $response = Gate::inspect('show', $account);
        $settings = Setting::getByTeam(auth()->user()->current_team_id);
        $timeZone = $settings["team_timezone"] ?? config('app.timezone');

        if (!$response->allowed()) {
            return redirect(route('finance'));
        }

        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters, $timeZone);

        return inertia($this->templates['show'], [
            "sectionTitle" => $account->name,
            'accountId' => $account->id,
            'resource' => $account,
            'transactions' => $account->transactionSplits(50, $startDate, $endDate, request()->only(['search', 'page', 'limit', 'direction'])),
            'stats' => $this->reportService->getAccountStats($account->id, $startDate, $endDate),
            "serverSearchOptions" => $this->getServerParams(),
        ]);
    }
}
