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
        $this->sorts = ['-date'];
        $this->includes = [
            'mainLine',
            'lines',
            'category',
            'transactionCategory',
            'mainLine.account',
            'category.account'
        ];
        $this->appends = [];
        $dates = $this->getFilterDates();
        $this->filters = [
            'date' => "{$dates['0']}~{$dates['1']}",
            'status' => Transaction::STATUS_VERIFIED
        ];
    }

    public function getIndexProps(Request $request, $accountId = null) {
        return  [
            "sectionTitle" => "Finance Transactions",
        ];
    }

    protected function parser($results) {
        return TransactionResource::collection($results);
    }

    public function import(Request $request) {
        Excel::import(new TransactionsImport($request->user()), $request->file('file'));
        return redirect()->back();
    }

    private function getOptionParams() {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        $queryParams['filters'] = $filters;
        return $queryParams;
    }
}
