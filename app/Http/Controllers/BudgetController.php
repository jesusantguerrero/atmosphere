<?php

namespace App\Http\Controllers;

use App\Events\BudgetAssigned;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryGroupCollection;
use App\Models\BudgetMovement;
use App\Models\Category;
use App\Models\Planner;
use App\Models\Transaction;
use Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Insane\Journal\Models\Core\Account;

class BudgetController extends InertiaController
{
    public function __construct(Category $category)
    {
        $this->model = $category;
        $this->templates = [
            "index" => 'Finance/Budget',
            "create" => 'BudgetCreate',
            "edit" => 'BudgetCreate'
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required|string|max:255|unique:categories',
            'amount' => 'numeric',
        ];
        $this->sorts = ['index'];
        $this->includes = ['subCategories', 'subCategories.budget', 'subCategories.budgets'];
        $this->filters = [
            'parent_id' => '$null',
            'resource_type' => 'transactions'
        ];
    }

    protected function getIndexProps(Request $request)
    {
        $queryParams = $request->query() ?? [];
        $queryParams['limit'] = $queryParams['limit'] ?? 50;
        $queryParams['date'] = $queryParams['date'] ?? date('Y-m-01');
        $teamId = $request->user()->current_team_id;

        return [
            'budgets' => CategoryGroupCollection::collection($this->getModelQuery($request)),
            "accounts" => Account::getByDetailTypes($teamId),
            "categories" => Category::where([
                'categories.team_id' => $teamId,
                'categories.resource_type' => 'transactions'
            ])
                ->orderBy('index')
                ->with('subCategories')
                ->get(),

        ];
    }

    public function addCategoryBudget(Request $request, $categoryId)
    {
        $category = Category::find($categoryId);
        $postData = $request->post();
        $category->budget()->create(array_merge($postData,[
            'name' => $category->name,
            'user_id' => $request->user()->id,
            'team_id' => $request->user()->current_team_id
        ]));
        return Redirect::back();
    }

    public function assignMonthBudget(Request $request, $categoryId, $month)
    {
        $category = Category::find($categoryId);
        $postData = $request->post();
        $monthBalance = $category->assignBudget($month, $postData);
        BudgetMovement::registerMovement($monthBalance, $postData);
        return Redirect::back();
    }

    public function updateCategoryBudget(Request $request, $categoryId)
    {
        $category = Category::find($categoryId);
        $postData = $request->post();
        $category->budget->update($postData);
        return Redirect::back();
    }

    public function addPlannedTransaction(Request $request) {
        $postData = $this->getPostData($request);
        $postData['status'] = Transaction::STATUS_PLANNED;
        $transaction = Transaction::create($postData);
        $transaction->createLines($postData, $postData['items'] ?? []);

        Planner::create(array_merge($postData ,[
            'dateable_type' => Transaction::class,
            'dateable_id' => $transaction['id'],
        ]));

        return Redirect::back();
    }

    public function markAsPaid(Request $request, $transactionId) {
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


        return Redirect::back();
    }
}
