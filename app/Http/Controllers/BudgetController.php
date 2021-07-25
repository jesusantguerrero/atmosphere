<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\MealPlan;
use App\Models\Transaction as ModelsTransaction;
use Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Insane\Journal\Category;
use Insane\Journal\Transaction;

class BudgetController extends InertiaController
{
    public function __construct(Budget $budget)
    {
        $this->model = $budget;
        $this->templates = [
            "index" => 'Budget',
            "create" => 'BudgetCreate',
            "edit" => 'BudgetCreate'
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'account_id' => 'required',
            'amount' => 'required'
        ];
        $this->includes = ['account'];
        $this->filters = [];
    }

    protected function getIndexProps(Request $request)
    {
        $queryParams = $request->query() ?? [];
        $queryParams['limit'] = $queryParams['limit'] ?? 50;
        $teamId = $request->user()->current_team_id;

        return [
            'budgets' => $this->getModelQuery($request),
            "categories" => Category::where([
                'depth' => 0,
                'display_id' => 'expenses'
            ])->with([
                'subCategories',
                'subcategories.accounts' => function ($query) use ($teamId) {
                    $query->where('team_id', '=', $teamId);
                }
            ])->get(),
        ];
    }

    public function addPlannedTransaction(Request $request) {
        $postData = $this->getPostData($request);
        $transaction = Transaction::create($postData);
        $transaction->createLines($postData, $postData['items'] ?? []);

        MealPlan::create(array_merge($postData ,[
            'dateable_type' => Transaction::class,
            'dateable_id' => $transaction['id'],
        ]));

        return Redirect::back();
    }

    public function markAsPaid(Request $request, $transactionId) {
        $transaction = ModelsTransaction::with(['schedule'])->find($transactionId);

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


        return Redirect::back();
    }
}
