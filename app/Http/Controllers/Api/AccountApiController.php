<?php

namespace App\Http\Controllers\Api;

use App\Domains\Transaction\Services\CreditCardReportService;
use App\Models\Account;
use App\Services\MultiCurrencyDisplayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Insane\Journal\Models\Core\Transaction;

class AccountApiController extends BaseController
{
    public function __construct(private MultiCurrencyDisplayService $multiCurrencyDisplayService)
    {
        $this->model = new Account;
        $this->searchable = ['name', 'display_id', 'alias'];
        $this->validationRules = [];
    }

    public function index($accountId = null)
    {
        if ($accountId) {
            return $this->teamQuery(request())->findOrFail($accountId);
        }

        return Account::getByDetailTypes(request()->user()->current_team_id);
    }

    public function unlinkedPayments(Account $account, CreditCardReportService $creditCardReportService)
    {
        return $creditCardReportService->getUnlinkedPayments(request()->user()->current_team_id, $account);
    }

    public function linkPayments(Account $account, Transaction $transaction, CreditCardReportService $creditCardReportService)
    {
        $this->authorize('update', $account);
        $this->authorize('update', $transaction);

        return $creditCardReportService->linkCreditCardPayment($account, $transaction);
    }

    public function bulkUpdate(Request $request)
    {
        $accounts = $request->post('accounts') ?? [];
        Account::where('team_id', $request->user()->current_team_id)->whereIn('id', array_keys($accounts))->chunkById(100, function ($savedAccounts) use ($accounts) {
            foreach ($savedAccounts as $account) {
                $account->update(Arr::except($accounts[$account->id], ['id', 'team_id', 'user_id']));
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Get multi-currency balances for an account
     *
     * @return JsonResponse
     */
    public function getMultiCurrencyBalances(Account $account)
    {
        $this->authorize('show', $account);

        try {
            $balances = $this->multiCurrencyDisplayService->getFormattedCurrencyBalances($account);
            $activitySummary = $this->multiCurrencyDisplayService->getMultiCurrencyActivitySummary($account, 'month');

            return response()->json([
                'success' => true,
                'data' => [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'is_multi_currency' => $account->isMultiCurrency(),
                    'primary_currency' => $account->getPrimaryCurrency(),
                    'secondary_currencies' => $account->getSecondaryCurrencies(),
                    'balances' => $balances,
                    'activity_summary' => $activitySummary,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve multi-currency balances: '.$e->getMessage(),
            ], 500);
        }
    }
}
