<?php

namespace App\Http\Controllers\Finance;

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Freesgen\Atmosphere\Http\Querify;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;
use Freesgen\Atmosphere\Http\InertiaController;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Automation\Models\AutomationService;
use App\Domains\Transaction\Services\BankConnectionService;
use App\Domains\Transaction\Services\CreditCardReportService;

class FinanceBillingCycleController extends InertiaController
{
    use Querify;

    const DateFormat = 'Y-m-d';

    private $reportService;

    public function __construct(Account $account, ReportService $reportService, private CreditCardReportService $creditCardReportService)
    {
        $this->reportService = $reportService;
        $this->model = $account;
        $this->templates = [
            'index' => 'Finance/Account',
            'show' => 'Finance/Account',
        ];
        $this->searchable = ['id', 'date', 'concent'];
        $this->includes = [
            'transactions',
        ];
        $this->appends = [];
    }

    public function show(Account $account)
    {
        $queryParams = request()->query();
        $response = Gate::inspect('show', $account);
        $settings = Setting::getByTeam(auth()->user()->current_team_id);
        $timeZone = $settings['team_timezone'] ?? config('app.timezone');

        if (! $response->allowed()) {
            return redirect(route('finance'));
        }

        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters, $timeZone);

        return inertia($this->templates['show'], [
            'sectionTitle' => $account->name,
            'accountId' => $account->id,
            'resource' => $account,
            'transactions' => $account->transactionSplits(50, $startDate, $endDate, request()->only(['search', 'page', 'limit', 'direction'])),
            'billingCycles' => $this->creditCardReportService->getBillingCyclesInPeriod($account->team_id, null, $endDate, $account->id, ['pending']),
            'stats' => $this->reportService->getAccountStats($account->id, $startDate, $endDate),
            'serverSearchOptions' => [],
        ]);
    }

    public function linkAccount(Account $account, AutomationService $automationService, BankConnectionService $bankConnectionService)
    {
        $data = $this->getPostData(request());
        $bankConnectionService->linkAccount($account, $automationService, $data['integration_id']);
    }

    public function linkCreditCardPayment(Account $account, Transaction $transaction, BankConnectionService $bankConnectionService)
    {
        $data = $this->getPostData(request());
        $bankConnectionService->linkCreditCardPayment($account, $transaction, $data['integration_id']);
    }
}
