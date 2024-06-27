<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;
use App\Domains\Transaction\Services\CreditCardReportService;

class AccountApiController extends BaseController
{
    public function __construct()
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
}
