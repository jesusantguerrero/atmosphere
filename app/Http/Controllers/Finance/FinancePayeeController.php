<?php

namespace App\Http\Controllers\Finance;

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Freesgen\Atmosphere\Http\Querify;
use Insane\Journal\Models\Core\Payee;
use Insane\Journal\Models\Core\Account;
use Freesgen\Atmosphere\Http\InertiaController;
use App\Domains\Transaction\Services\BHDService;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Automation\Models\AutomationService;

class FinancePayeeController extends InertiaController
{
    use Querify;

    const DateFormat = 'Y-m-d';

    private $reportService;

    public function __construct(Payee $payee, ReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->model = $payee;
        $this->templates = [
            'index' => 'Finance/Payee',
            'show' => 'Finance/Payee',
        ];
        $this->searchable = ['id', 'date', 'concent'];
        $this->includes = [
            'transactions',
        ];
        $this->appends = [];
    }

    public function show(Payee $payee)
    {
        $queryParams = request()->query();
        $response = Gate::inspect('show', $payee);
        $settings = Setting::getByTeam(auth()->user()->current_team_id);
        $timeZone = $settings['team_timezone'] ?? config('app.timezone');

        if (! $response->allowed()) {
            return redirect(route('finance'));
        }

        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters, $timeZone);

        return inertia($this->templates['show'], [
            'sectionTitle' => $payee->name,
            'payeeId' => $payee->id,
            'resource' => $payee,
            'transactions' => $payee->transactionSplits(50, $startDate, $endDate, request()->only(['search', 'page', 'limit', 'direction'])),
            // 'stats' => $this->reportService->getPayeeStats($payee->id, $startDate, $endDate),
            'serverSearchOptions' => [],
        ]);
    }
}
