<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Integration\Concerns\PlannedTransactionDTO;
use App\Domains\Transaction\Actions\FindLinkedDrafts;
use App\Domains\Transaction\Actions\FindLinkedTransactions;
use App\Domains\Transaction\Exports\TransactionExport;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Resources\TransactionResource;
use App\Domains\Transaction\Services\PlannedTransactionService;
use App\Domains\Transaction\Services\TransactionService;
use App\Http\Controllers\Traits\QuerifySlim;
use App\Services\MultiCurrencyDisplayService;
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;
use Freesgen\Atmosphere\Http\InertiaController;
use Freesgen\Atmosphere\Http\Querify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use Maatwebsite\Excel\Facades\Excel;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\ArrayTransformerConfig;

class FinanceTransactionController extends InertiaController
{
    use Querify;

    const DateFormat = 'Y-m-d';

    public function __construct(
        Transaction $transaction,
        private PlannedTransactionService $plannedService,
        private MultiCurrencyDisplayService $multiCurrencyDisplayService
    ) {
        $this->model = $transaction;
        $this->templates = [
            'index' => 'Finance/Transactions',
        ];
        $this->searchable = ['transactions.description', 'date'];
        $this->sorts = ['-date'];
        $this->includes = [
            'mainLine',
            'lines',
            'category',
            'counterAccount',
            'mainLine.account',
            'counterLine.account',
        ];
        $this->appends = [];
        $this->filters = [
            'status' => Transaction::STATUS_VERIFIED,
        ];
    }

    protected function list(Request $request)
    {
        $dates = $this->getFilterDates();
        $filters = $request->query('filter');
        $status = $filters['status'] ?? null;

        $query = new QuerifySlim([
            'searchable' => ['transactions.date', 'transactions.description'],
            'sorts' => ['-date'],
            'includes' => [
                'mainLine',
                'lines',
                'category',
                'counterAccount',
                'mainLine.account',
                'counterLine.account',
            ],
            'filters' => [
                'date' => $status == 'draft' ? null : "{$dates['0']}~{$dates['1']}",
                'status' => Transaction::STATUS_VERIFIED,
            ],
        ]);

        return $query->getModelQuery($request, 'transactions', function ($query) {
            $query->selectRaw('
                transactions.id,
                transactions.description,
                transactions.date,
                transactions.direction,
                transactions.status,
                transactions.total,
                transactions.account_id,
                transactions.counter_account_id,
                transactions.payee_id,
                categories.name category_name,
                payees.name payee_name,
                ca.name counter_account_name,
                accounts.name account_name,
                linked.id linked_transaction_id,
                linked.total linked_transaction_total
            ')
                ->leftJoin('categories', 'categories.id', 'transactions.category_id')
                ->leftJoin('payees', 'payees.id', 'transactions.payee_id')
                ->leftJoin(DB::raw('accounts ca'), 'ca.id', 'transactions.counter_account_id')
                ->leftJoin('accounts', 'accounts.id', 'transactions.account_id')
                ->leftJoin('linked_transactions', 'transactions.id', 'linked_transactions.transaction_id')
                ->leftJoin(DB::raw('transactions linked'), 'linked.id', 'linked_transactions.linked_transaction_id');
        });
    }

    protected function index(Request $request)
    {
        $resourceName = $this->resourceName ?? $this->model->getTable();

        return inertia($this->templates['index'], [
            $resourceName => [],
            'serverSearchOptions' => $this->getServerParams(),
        ]);
    }

    public function getByState(Request $request, $state = 'verified')
    {
        $this->filters['status'] = $state;

        return $this->index($request);
    }

    public function getIndexProps(Request $request, $accountId = null): array
    {
        return [
            'sectionTitle' => 'Finance Transactions',
        ];
    }

    protected function parser($results)
    {
        // Enhance transactions with multi-currency display information
        $enhancedResults = $this->multiCurrencyDisplayService->enhanceTransactionDisplay($results);

        return TransactionResource::collection($enhancedResults);
    }

    public function import(Request $request)
    {
        $user = $request->user();
        $file = request()->file('file')->store('temp');
        $path = storage_path('app').'/'.$file;

        TransactionService::importAndSave($user, $path);

        return back()->with('flash', [
            'banner' => 'You will notified once the import is completed',
        ]);
    }

    public function addPlanned(Request $request)
    {
        $postData = $this->getPostData($request);
        $this->plannedService->add(PlannedTransactionDTO::from($postData));

        return redirect()->back();
    }

    public function markPlannedAsPaid(Request $request, $transactionId)
    {
        $transaction = Transaction::with(['schedule'])->find($transactionId);

        if ($transaction->team_id == $request->user()->current_team_id) {
            $schedule = $transaction->schedule;
            $rule = (new Rule)
                ->setStartDate(new \DateTime($schedule['date']))
                ->setTimezone($schedule->timezone)
                ->setFreq($schedule->frequency);

            $transformer = new ArrayTransformer;
            $transformerConfig = new ArrayTransformerConfig;
            $transformerConfig->enableLastDayOfMonthFix();
            $transformer->setConfig($transformerConfig);

            $nextDate = $transformer->transform($rule)[1];

            $transaction->copy(['status' => 'verified']);
            $transaction->update(['date' => $nextDate->getStart()->format('Y-m-d')]);
            $transaction->schedule->update(['date' => $nextDate->getStart()->format('Y-m-d')]);
        }

        return redirect()->back();
    }

    public function export()
    {
        $teamId = request()->user()->current_team_id;
        $today = now()->format('Y-m-d');

        return Excel::download(new TransactionExport($teamId), "transactions_as_of_{$today}.xlsx");
    }

    public function exportCsv(Request $request)
    {
        $teamId = $request->user()->current_team_id;
        [$startDate, $endDate] = $this->parseDateRangeFilter($request);
        $accountId = $request->query('filter')['account_id'] ?? null;

        $filename = $this->buildExportFilename('transactions', $startDate, $endDate, 'csv');

        return Excel::download(
            new TransactionExport($teamId, $startDate, $endDate, $accountId ? (int) $accountId : null),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        $teamId = $request->user()->current_team_id;
        [$startDate, $endDate] = $this->parseDateRangeFilter($request);
        $accountId = $request->query('filter')['account_id'] ?? null;

        $query = DB::table('transactions')
            ->select([
                'transactions.date',
                'payees.name as payee_name',
                'transactions.description',
                'categories.name as category_name',
                'accounts.name as account_name',
                'transactions.direction',
                'transactions.total',
                'transactions.currency_code',
            ])
            ->leftJoin('payees', 'payees.id', 'transactions.payee_id')
            ->leftJoin('categories', 'categories.id', 'transactions.category_id')
            ->leftJoin('accounts', 'accounts.id', 'transactions.account_id')
            ->where('transactions.team_id', $teamId)
            ->where('transactions.status', 'verified')
            ->orderBy('transactions.date', 'desc');

        if ($startDate && $endDate) {
            $query->whereBetween('transactions.date', [$startDate, $endDate]);
        }

        if ($accountId) {
            $query->where('transactions.account_id', (int) $accountId);
        }

        $transactions = $query->get();

        $income = $transactions->where('direction', 'DEPOSIT')->sum('total');
        $expenses = $transactions->where('direction', 'WITHDRAW')->sum('total');

        $accountName = $accountId
            ? DB::table('accounts')->where('id', $accountId)->value('name')
            : null;

        $filename = $this->buildExportFilename('transactions', $startDate, $endDate, 'pdf');

        $html = view('exports.transactions', [
            'transactions' => $transactions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'accountName' => $accountName,
            'income' => $income,
            'expenses' => $expenses,
            'net' => $income - $expenses,
        ])->render();

        $dompdfOptions = new DompdfOptions;
        $dompdfOptions->set('isHtml5ParserEnabled', true);
        $dompdfOptions->set('isPhpEnabled', false);

        $dompdf = new Dompdf($dompdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfOutput = $dompdf->output();

        return response()->streamDownload(
            fn () => print ($pdfOutput),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    private function parseDateRangeFilter(Request $request): array
    {
        $dateFilter = $request->query('filter')['date'] ?? null;

        if (! $dateFilter) {
            return [null, null];
        }

        $parts = explode('~', $dateFilter);

        return [
            $parts[0] ?? null,
            $parts[1] ?? $parts[0] ?? null,
        ];
    }

    private function buildExportFilename(string $prefix, ?string $startDate, ?string $endDate, string $extension): string
    {
        $today = now()->format('Y-m-d');

        if ($startDate && $endDate) {
            return "{$prefix}_{$startDate}_to_{$endDate}.{$extension}";
        }

        return "{$prefix}_as_of_{$today}.{$extension}";
    }

    // linked transactions
    public function findLinked(Transaction $transaction)
    {
        (new FindLinkedTransactions(
            $transaction->team_id,
            $transaction->user_id,
            [
                'id' => $transaction->id,
                'date' => $transaction->date,
                'total' => $transaction->total,
            ]
        ))->handle();

        return redirect()->back();
    }

    public function findLinkedDrafts()
    {
        (new FindLinkedDrafts(request()->user()->current_team_id))->handle();

        return redirect()->back();
    }

    private function getOptionParams()
    {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        $queryParams['filters'] = $filters;

        return $queryParams;
    }

    public function bulkDelete(Request $request)
    {
        $items = $request->post('data');

        ReconciliationEntry::whereIn('transaction_id', $items)->delete();

        Transaction::whereIn('id', $items)->delete();
    }

    public function approve(Transaction $transaction)
    {
        $this->authorize('update', $transaction);
        $transaction->approve();

        return response()->json(['message' => 'Transaction approved']);
    }
}
