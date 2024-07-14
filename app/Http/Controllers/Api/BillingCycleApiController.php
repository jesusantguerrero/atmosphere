<?php

namespace App\Http\Controllers\Api;

use Exception;
use Insane\Journal\Models\Core\Transaction;
use App\Domains\Transaction\Models\BillingCycle;
use App\Domains\Transaction\Services\CreditCardReportService;

class BillingCycleApiController extends BaseController
{
    public function __construct()
    {
        $this->model = new BillingCycle();
        $this->searchable = ['name', 'display_id', 'alias'];
        $this->validationRules = [];
    }


    public function linkPayments(CreditCardReportService $creditCardReportService, BillingCycle $billingCycle,Transaction $transaction) {
        try {
            return $creditCardReportService->linkCreditCardPayment($billingCycle, $transaction, request()->post());
          } catch (Exception $e) {
              return response([
                'status' => [
                    'message' => $e->getMessage()
                ]
              ], 400);
          }
    }

}
