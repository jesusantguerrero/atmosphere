<?php

namespace App\Http\Controllers;

use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Resources\TransactionResource;
use App\Domains\Transaction\Services\TransactionService;
use App\Imports\TransactionsImport;
use App\Models\Planner;
use App\Notifications\TransactionsImported;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
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
        $user = $request->user();
        Excel::import(new TransactionsImport($user), $request->file('file'));
        $user->notify(new TransactionsImported());
        return redirect()->back();
    }

    public function addPlanned(Request $request) {
        $postData = $this->getPostData($request);
        $postData['status'] = Transaction::STATUS_PLANNED;
        $transaction = Transaction::create($postData);
        $transaction->createLines($postData['items'] ?? []);

        Planner::create(array_merge($postData ,[
            'dateable_type' => Transaction::class,
            'dateable_id' => $transaction['id'],
        ]));

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

    private function getOptionParams() {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        $queryParams['filters'] = $filters;
        return $queryParams;
    }
}