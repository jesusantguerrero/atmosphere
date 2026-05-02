<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationService;
use App\Domains\Automation\Services\LogerAutomationService;
use App\Domains\Transaction\Actions\MapBankStatementToLoger;
use App\Domains\Transaction\Actions\ParseBankCsv;
use App\Domains\Transaction\Actions\ParseBankPdf;
use App\Domains\Transaction\Services\BankConnectionService;
use App\Domains\Transaction\Services\CreditCardReportService;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Transaction\Services\TransactionService;
use App\Models\Setting;
use Freesgen\Atmosphere\Http\InertiaController;
use Freesgen\Atmosphere\Http\Querify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;

class FinanceAccountController extends InertiaController
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
            'stats' => $this->reportService->getAccountStats($account->id, $startDate, $endDate),
            'startingBalance' => $this->reportService->getAccountBalanceBefore($account->id, $startDate),
            'serverSearchOptions' => [],
        ]);
    }

    public function linkAccount(Account $account, AutomationService $automationService, BankConnectionService $bankConnectionService)
    {
        $data = $this->getPostData(request());
        $bankConnectionService->linkAccount($account, $automationService, $data['integration_id']);
    }

    public function linkAccountToBank(Account $account, Request $request, BankConnectionService $bankConnectionService)
    {
        $data = $request->validate([
            'bank_code' => ['required', 'string'],
            'integration_id' => ['required', 'integer'],
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
        $data = $this->getPostData(request());
        $bankConnectionService->linkCreditCardPayment($account, $transaction, $data['integration_id']);
    }

    public function importPdf(Request $request, Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $file = $request->file('file')->store('temp');
        $path = storage_path('app').'/'.$file;

        $rows = ParseBankPdf::parse($path);
        unlink($path);

        if (empty($rows)) {
            return back()->with('flash', [
                'banner' => 'No transactions found in the PDF',
                'bannerStyle' => 'danger',
            ]);
        }

        $user = $request->user();
        $session = [
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
        ];

        $imported = 0;
        $skipped = 0;
        $transactionService = new TransactionService;

        foreach ($rows as $row) {
            $transactionData = MapBankStatementToLoger::parse($row, $session, $account->id);

            $duplicate = $transactionService->findDuplicateByBankReference($transactionData)
                ?? $transactionService->findIfDuplicated($transactionData);
            if ($duplicate) {
                $skipped++;

                continue;
            }

            Transaction::createTransaction($transactionData);
            $imported++;
        }

        return back()->with('flash', [
            'banner' => "Imported {$imported} transactions as drafts".($skipped ? " ({$skipped} duplicates skipped)" : ''),
        ]);
    }

    public function importCsv(Request $request, Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $file = $request->file('file')->store('temp');
        $path = storage_path('app').'/'.$file;

        $rows = ParseBankCsv::parse($path);
        unlink($path);

        if (empty($rows)) {
            return back()->with('flash', [
                'banner' => 'No transactions found in the CSV',
                'bannerStyle' => 'danger',
            ]);
        }

        $user = $request->user();
        $session = [
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
        ];

        $imported = 0;
        $skipped = 0;
        $transactionService = new TransactionService;

        foreach ($rows as $row) {
            $transactionData = MapBankStatementToLoger::parse($row, $session, $account->id);

            $duplicate = $transactionService->findDuplicateByBankReference($transactionData)
                ?? $transactionService->findIfDuplicated($transactionData);
            if ($duplicate) {
                $skipped++;

                continue;
            }

            Transaction::createTransaction($transactionData);
            $imported++;
        }

        return back()->with('flash', [
            'banner' => "Imported {$imported} transactions as drafts".($skipped ? " ({$skipped} duplicates skipped)" : ''),
        ]);
    }

    public function closeAccount(Account $account)
    {
        $data = $this->getPostData(request());
        $account->closed_at = $data['closed_at'];
        $account->archived = $data['archived'];
        $account->status = $data['status'];
        $account->save();
    }
}
