<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Freesgen\Atmosphere\Http\Querify;
use App\Http\Controllers\Traits\QuerifySlim;
use App\Domains\Transaction\Models\Transaction;
use Freesgen\Atmosphere\Http\InertiaController;
use App\Domains\Transaction\Actions\FindLinkedDrafts;
use App\Domains\Transaction\Exports\TransactionExport;
use App\Domains\Transaction\Services\TransactionService;
use App\Domains\Transaction\Resources\TransactionResource;
use App\Domains\Transaction\Actions\FindLinkedTransactions;
use App\Domains\Transaction\Services\PlannedTransactionService;

class FinanceTransactionController extends InertiaController
{
    use Querify;

    const DateFormat = 'Y-m-d';

    public function __construct(Transaction $transaction, private PlannedTransactionService $plannedService)
    {
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
        return TransactionResource::collection($results);
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
        $this->plannedService($this->getPostData($request));

        return redirect()->back();
    }

    public function markPlannedAsPaid(Request $request, $transactionId)
    {
        $transaction = Transaction::with(['schedule'])->find($transactionId);

        if ($transaction->team_id == $request->user()->current_team_id) {
            $schedule = $transaction->schedule;
            $rule = (new \Recurr\Rule())
                ->setStartDate(new \DateTime($schedule['date']))
                ->setTimezone($schedule->timezone)
                ->setFreq($schedule->frequency);

            $transformer = new \Recurr\Transformer\ArrayTransformer();
            $transformerConfig = new \Recurr\Transformer\ArrayTransformerConfig();
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
        $dataToExport = new TransactionExport(TransactionService::getForExport(request()->user()->current_team_id));
        $today = now()->format('Y-m-d');

        return Excel::download($dataToExport, "transactions_as_of_{$today}.xlsx");
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
        Transaction::whereIn('id', $items)->delete();
    }
}
