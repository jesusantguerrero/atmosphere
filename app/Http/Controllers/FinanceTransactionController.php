<?php

namespace App\Http\Controllers;

use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Resources\TransactionResource;
use App\Domains\Transaction\Services\TransactionService;
use App\Imports\TransactionsImport;
use Carbon\Carbon;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;
use Freesgen\Atmosphere\Http\Querify;
use Maatwebsite\Excel\Facades\Excel;

class FinanceTransactionController extends InertiaController {
    use Querify;
    const DateFormat = 'Y-m-d';

    public function __construct(Transaction $transaction)
    {
        $this->model = $transaction;
        $this->templates = [
            'index' => 'Finance/Transactions'
        ];
        $this->searchable = ["id", "date"];
        $this->sorts = [];
        $this->includes = [];
        $this->appends = [];
        $this->filters = [];
    }

    public function index(Request $request, $accountId = null) {
        $options = $this->getOptionParams();
        $teamId = $request->user()->current_team_id;
        $transactionService = new TransactionService();

        $transactions = $accountId
        ? $transactionService->getForAccount($accountId, $teamId, $options)
        : $transactionService->getList($teamId, $options);

        return Jetstream::inertia()->render($request, 'Finance/Transactions', [
            "sectionTitle" => "Finance Transactions",
            "transactions" => TransactionResource::collection($transactions),
            "accountId" => $accountId,
            "serverSearchOptions" => $options
        ]);
    }

    public function import(Request $request) {
        Excel::import(new TransactionsImport($request->user()), $request->file('file'));
        return redirect()->back();
    }

    private function getOptionParams() {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);
        $queryParams['filters'] = $filters;
        $queryParams['startDate'] = $startDate;
        $queryParams['endDate'] = $endDate;
        return $queryParams;
    }
}
