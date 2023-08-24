<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Transaction\Actions\FindLinkedTransactions;
use App\Domains\Transaction\Exports\TransactionExport;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Resources\TransactionResource;
use App\Domains\Transaction\Services\PlannedTransactionService;
use App\Domains\Transaction\Services\TransactionService;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Freesgen\Atmosphere\Http\Querify;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class FinanceTransactionController extends InertiaController {
    use Querify;
    const DateFormat = 'Y-m-d';

    public function __construct(Transaction $transaction, private PlannedTransactionService $plannedService)
    {
        $this->model = $transaction;
        $this->templates = [
            'index' => 'Finance/Transactions'
        ];
        $this->searchable = ["id", "date"];
        $this->sorts = ['-date'];
        $this->includes = [
            'mainLine',
            'lines',
            'category',
            'counterAccount',
            'mainLine.account',
            'counterLine.account'
        ];
        $this->appends = [];
        $dates = $this->getFilterDates();
        $this->filters = [
            'date' => "{$dates['0']}~{$dates['1']}",
            'status' => Transaction::STATUS_VERIFIED
        ];
    }

    protected function list(Request $request) {
        return  $this->parser($this->getModelQuery($request));
    }

    protected function index(Request $request) {
        $resourceName = $this->resourceName ?? $this->model->getTable();
        return inertia($this->templates['index'],[
            $resourceName => fn () => $this->parser($this->getModelQuery($request)),
            "serverSearchOptions" => $this->getServerParams(),
        ]);
    }

    public function getByState(Request $request, $state = 'verified') {
        $this->filters['status'] = $state;
        return $this->index($request);
    }

    public function getIndexProps(Request $request, $accountId = null): array {
        return  [
            "sectionTitle" => "Finance Transactions",
        ];
    }

    protected function parser($results) {
        return TransactionResource::collection($results);
    }

    public function import(Request $request) {
        $user = $request->user();
        $file = request()->file('file')->store('temp');
        $path = storage_path('app'). '/' . $file;

        TransactionService::importAndSave($user, $path);

        return back()->with('flash', [
            'banner' => "You will notified once the import is completed"
        ]);
    }

    public function addPlanned(Request $request) {
        $this->plannedService($this->getPostData($request));
        return redirect()->back();
    }

    public function markPlannedAsPaid(Request $request, $transactionId) {
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

    public function export() {
        $dataToExport = new TransactionExport(TransactionService::getForExport(request()->user()->current_team_id));
        $today = now()->format('Y-m-d');
        return Excel::download($dataToExport, "transactions_as_of_{$today}.xlsx");
    }

    // linked transactions
    public function findLinked(Transaction $transaction) {
        (new FindLinkedTransactions(
            $transaction->team_id,
            $transaction->user_id,
            [
                "id" => $transaction->id,
                "date" => $transaction->date,
                "total" => $transaction->total
            ]
        ))->handle();

        return redirect()->back();
    }

    private function getOptionParams() {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        $queryParams['filters'] = $filters;
        return $queryParams;
    }
}
