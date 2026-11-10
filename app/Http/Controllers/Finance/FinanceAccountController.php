<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationService;
use App\Domains\Automation\Services\LogerAutomationService;
use App\Domains\Transaction\Services\BankConnectionService;
use App\Domains\Transaction\Services\CreditCardReportService;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Transaction\Services\StatementImportService;
use App\Models\Account;
use App\Models\Setting;
use Freesgen\Atmosphere\Http\InertiaController;
use Freesgen\Atmosphere\Http\Querify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Inertia\Inertia;
use Inertia\Response;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use Insane\Journal\Models\Core\Transaction;

class FinanceAccountController extends InertiaController
{
    use Querify;

    const DateFormat = 'Y-m-d';

    private $reportService;

    public function index(Request $request): Response
    {
        return Inertia::render('Finance/Account', [
            'accounts' => Account::getByDetailTypes($request->user()->current_team_id),
            'transactions' => [],
            'billingCycles' => [],
            'stats' => ['total' => 0, 'credit' => 0, 'debit' => 0],
            'serverSearchOptions' => [],
        ]);
    }

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

    /**
     * Accounts are created through the AccountModal in the accounts ledger, not
     * a dedicated page -- so the resource `create` route had no handler and
     * every first-run CTA / deep-link to /finance/accounts/create returned 500.
     * Redirect to the accounts overview with a flag that auto-opens the
     * add-account modal (preserving an optional ?type= hint, e.g. credit_card).
     * The overview reads the flag on every screen size; the transactions page
     * only mounts the ledger on mobile.
     */
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('finance.accounts.index', array_filter([
            'newAccount' => 1,
            'type' => $request->query('type'),
        ]));
    }

    public function show(Account $account)
    {
        $queryParams = request()->query();
        $response = Gate::inspect('show', $account);
        $settings = Setting::getByTeam(auth()->user()->current_team_id);
        $timeZone = $settings['team_timezone'] ?? config('app.timezone');

        if (! $response->allowed()) {
            return redirect()->route('finance.transactions');
        }

        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters, $timeZone);

        $transactions = $account->transactionSplits(50, $startDate, $endDate, request()->only(['search', 'page', 'limit', 'direction']));
        $drafts = $account->transactionSplits(100, $startDate, $endDate, ['status' => 'draft']);

        $allTransactionIds = $transactions->pluck('id')->merge($drafts->pluck('id'))->unique();
        $reconciledIds = ReconciliationEntry::whereIn('transaction_id', $allTransactionIds)
            ->where('matched', true)
            ->pluck('transaction_id')
            ->flip();

        $transactions->each(fn ($t) => $t->is_reconciled = $reconciledIds->has($t->id));
        $drafts->each(fn ($t) => $t->is_reconciled = $reconciledIds->has($t->id));

        return inertia($this->templates['show'], [
            'sectionTitle' => $account->name,
            'accountId' => $account->id,
            'resource' => $account,
            'transactions' => $transactions,
            'drafts' => $drafts,
            'billingCycles' => $this->creditCardReportService->getBillingCyclesInPeriod($account->team_id, $startDate, $endDate, $account->id),
            'lastCreditCardPayment' => $this->creditCardReportService->getLastPayment($account->team_id, $account->id),
            'stats' => $this->reportService->getAccountStats($account->id, $startDate, $endDate),
            'startingBalance' => $this->reportService->getAccountBalanceBefore($account->id, $startDate),
            'serverSearchOptions' => [],
        ]);
    }

    public function linkAccount(Account $account, AutomationService $automationService, BankConnectionService $bankConnectionService, Request $request)
    {
        $this->authorize('update', $account);
        $data = $request->validate([
            'integration_id' => ['required', 'integer', $this->teamIntegrationRule($request)],
        ]);

        $bankConnectionService->linkAccount($account, $automationService, $data['integration_id']);
    }

    public function linkAccountToBank(Account $account, Request $request, BankConnectionService $bankConnectionService)
    {
        $this->authorize('update', $account);
        $data = $request->validate([
            'bank_code' => ['required', 'string'],
            'integration_id' => ['required', 'integer', $this->teamIntegrationRule($request)],
        ]);

        $bankConnectionService->linkAccountToBank($account, $data['bank_code'], $data['integration_id']);
    }

    public function updateBankCode(Account $account, Request $request): RedirectResponse
    {
        abort_unless(
            $request->user()->belongsToTeam($account->team),
            403,
            'You do not own this account.'
        );

        $data = $request->validate([
            'bank_code' => ['nullable', 'string', 'max:64'],
        ]);

        $account->update([
            'bank_code' => $data['bank_code'] ? trim($data['bank_code']) : null,
        ]);

        return back();
    }

    public function syncEmails(Account $account, Request $request): RedirectResponse
    {
        $this->authorize('update', $account);

        $data = $request->validate([
            'startDate' => ['required', 'date_format:Y-m-d'],
            'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate'],
        ]);

        abort_unless($account->bank_code, 422, 'Account is not linked to a bank');

        $automation = Automation::where('automatable_id', $account->id)
            ->where('automatable_type', get_class($account))
            ->first();

        abort_unless($automation, 404, 'No automation linked to this account');

        ob_start();
        LogerAutomationService::run($automation, [
            'backfill' => true,
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
        ]);
        ob_end_clean();

        return back();
    }

    public function linkCreditCardPayment(Account $account, Transaction $transaction, BankConnectionService $bankConnectionService)
    {
        $this->authorize('update', $account);
        $this->authorize('update', $transaction);
        $data = $this->getPostData(request());
        $bankConnectionService->linkCreditCardPayment($account, $transaction, $data['integration_id']);
    }

    public function importPdf(Request $request, Account $account, StatementImportService $statements): RedirectResponse
    {
        $this->authorize('update', $account);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        return $this->importedResponse($statements->import($request->file('file'), $account, $request->user()), 'PDF');
    }

    public function importCsv(Request $request, Account $account, StatementImportService $statements): RedirectResponse
    {
        $this->authorize('update', $account);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        return $this->importedResponse($statements->import($request->file('file'), $account, $request->user()), 'CSV');
    }

    /**
     * @param  array{found: int, imported: int, skipped: int}  $result
     */
    private function importedResponse(array $result, string $format): RedirectResponse
    {
        if ($result['found'] === 0) {
            return back()->with('flash', [
                'banner' => "No transactions found in the {$format}",
                'bannerStyle' => 'danger',
            ]);
        }

        return back()->with('flash', [
            'banner' => "Imported {$result['imported']} transactions as drafts".($result['skipped'] ? " ({$result['skipped']} duplicates skipped)" : ''),
        ]);
    }

    public function closeAccount(Account $account)
    {
        $this->authorize('update', $account);
        $data = $this->getPostData(request());
        $account->closed_at = $data['closed_at'];
        $account->archived = $data['archived'];
        $account->status = $data['status'];
        $account->save();
    }

    private function teamIntegrationRule(Request $request): Exists
    {
        return Rule::exists('integrations', 'id')->where('team_id', $request->user()->current_team_id);
    }
}
