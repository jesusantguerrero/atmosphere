<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;
use App\Domains\Transaction\Services\CreditCardReportService;
use App\Services\MultiCurrencyDisplayService;

class AccountApiController extends BaseController
{
    public function __construct(private MultiCurrencyDisplayService $multiCurrencyDisplayService)
    {
        $this->model = new Account();
        $this->searchable = ['name', 'display_id', 'alias'];
        $this->validationRules = [];
    }

    public function index($accountId = null) {
        if ($accountId) {
          return Account::find($accountId);
        }
        return Account::getByDetailTypes(request()->user()->current_team_id);
      }

    public function unlinkedPayments(Account $account, CreditCardReportService $creditCardReportService) {
        return $creditCardReportService->getUnlinkedPayments(request()->user()->current_team_id, $account);
    }

    public function linkPayments(Account $account, Transaction $transaction, CreditCardReportService $creditCardReportService) {
        return $creditCardReportService->linkCreditCardPayment($account, $transaction);
    }


    public function bulkUpdate(Request $request)
    {
        $accounts = $request->post('accounts');
        Account::whereIn('id', array_keys($accounts))->chunkById(100, function ($savedAccounts) use ($accounts) {
            foreach ($savedAccounts as $account) {
                $account->update($accounts[$account->id]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Get multi-currency balances for an account
     * 
     * @param Account $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMultiCurrencyBalances(Account $account)
    {
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
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve multi-currency balances: ' . $e->getMessage()
            ], 500);
        }
    }
}
