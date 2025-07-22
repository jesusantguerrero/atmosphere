<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MultiCurrencyTransactionResource;
use App\Models\Account;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\MultiCurrencyTransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;

class MultiCurrencyTransactionController extends Controller
{
    public function __construct(
        private MultiCurrencyTransactionService $multiCurrencyService
    ) {}

    /**
     * Create a transaction with multi-currency support
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'total' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
            'payee_id' => 'nullable|exists:payees,id',
            'counter_account_id' => 'nullable|exists:accounts,id',
            'direction' => 'required|in:credit,debit',
            'status' => 'nullable|in:draft,verified',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        
        // Check if user can create transactions for this account
        Gate::authorize('create', [Transaction::class, $account]);

        // Add user and team information
        $transactionData = array_merge($validated, [
            'team_id' => $request->user()->current_team_id,
            'user_id' => $request->user()->id,
            'status' => $validated['status'] ?? 'verified',
        ]);

        try {
            // Check if this is a multi-currency transaction
            if ($account->isMultiCurrency() && $validated['currency_code'] !== $account->getPrimaryCurrency()) {
                // Use multi-currency service for secondary currency transactions
                $transaction = $this->multiCurrencyService->createCreditCardTransaction($transactionData);
            } else {
                // Use standard transaction creation for primary currency
                $transaction = Transaction::create($transactionData);
            }

            return response()->json([
                'success' => true,
                'data' => new MultiCurrencyTransactionResource($transaction),
                'message' => 'Transaction created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Process a credit card payment with currency conversion
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'total' => 'required|numeric|min:0.01',
            'exchange_amount' => 'required|numeric|min:0.01',
            'secondary_currency' => 'required|string|size:3',
            'payment_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'payee_id' => 'nullable|exists:payees,id',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        
        // Check if user can create transactions for this account
        Gate::authorize('create', [Transaction::class, $account]);

        // Verify account supports multi-currency
        if (!$account->isMultiCurrency()) {
            return response()->json([
                'success' => false,
                'message' => 'Account does not support multi-currency transactions'
            ], 422);
        }

        // Verify account supports the secondary currency
        if (!$account->supportsCurrency($validated['secondary_currency'])) {
            return response()->json([
                'success' => false,
                'message' => "Account does not support currency {$validated['secondary_currency']}"
            ], 422);
        }

        try {
            $paymentDate = Carbon::parse($validated['payment_date']);
            
            $additionalData = [
                'description' => $validated['description'] ?? "Credit card payment - {$validated['secondary_currency']} to {$account->getPrimaryCurrency()}",
                'category_id' => $validated['category_id'],
                'payee_id' => $validated['payee_id'],
                'direction' => 'credit', // Payment reduces credit card balance
                'status' => 'verified',
            ];

            $transaction = $this->multiCurrencyService->processCreditCardPayment(
                $account,
                $validated['total'],
                $validated['exchange_amount'],
                $validated['secondary_currency'],
                $paymentDate,
                $additionalData
            );

            return response()->json([
                'success' => true,
                'data' => new MultiCurrencyTransactionResource($transaction),
                'message' => 'Payment processed successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get currency balances for an account
     * 
     * @param Request $request
     * @param int $accountId
     * @return JsonResponse
     */
    public function getCurrencyBalances(Request $request, int $accountId): JsonResponse
    {
        $account = Account::findOrFail($accountId);
        
        // Check if user can view this account
        Gate::authorize('view', $account);

        try {
            $balances = $account->getAllCurrencyBalances();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'is_multi_currency' => $account->isMultiCurrency(),
                    'primary_currency' => $account->getPrimaryCurrency(),
                    'secondary_currencies' => $account->getSecondaryCurrencies(),
                    'balances' => $balances
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve currency balances: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending balances for an account (credit card specific)
     * 
     * @param Request $request
     * @param int $accountId
     * @return JsonResponse
     */
    public function getPendingBalances(Request $request, int $accountId): JsonResponse
    {
        $account = Account::findOrFail($accountId);
        
        // Check if user can view this account
        Gate::authorize('view', $account);

        try {
            $pendingBalances = $this->multiCurrencyService->getAllPendingBalances($account);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'pending_balances' => $pendingBalances
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending balances: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single transaction with multi-currency information
     * 
     * @param Request $request
     * @param int $transactionId
     * @return JsonResponse
     */
    public function show(Request $request, int $transactionId): JsonResponse
    {
        try {
            $transaction = Transaction::with(['account', 'counterAccount', 'category', 'payee'])
                ->where('team_id', $request->user()->current_team_id)
                ->findOrFail($transactionId);

            // Check if user can view this transaction
            Gate::authorize('view', $transaction);

            return response()->json([
                'success' => true,
                'data' => new MultiCurrencyTransactionResource($transaction)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transactions with multi-currency information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'nullable|exists:accounts,id',
            'currency_code' => 'nullable|string|size:3',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        try {
            $query = Transaction::with(['account', 'counterAccount', 'category', 'payee'])
                ->where('team_id', $request->user()->current_team_id);

            // Filter by account if specified
            if (!empty($validated['account_id'])) {
                $query->where('account_id', $validated['account_id']);
            }

            // Filter by currency if specified
            if (!empty($validated['currency_code'])) {
                $query->where('currency_code', $validated['currency_code']);
            }

            // Filter by date range if specified
            if (!empty($validated['start_date'])) {
                $query->where('date', '>=', $validated['start_date']);
            }
            if (!empty($validated['end_date'])) {
                $query->where('date', '<=', $validated['end_date']);
            }

            $limit = $validated['limit'] ?? 20;
            $transactions = $query->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => MultiCurrencyTransactionResource::collection($transactions->items()),
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions: ' . $e->getMessage()
            ], 500);
        }
    }


}