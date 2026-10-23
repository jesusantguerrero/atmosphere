<?php

namespace App\Http\Controllers\Api;

use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\MultiCurrencyTransactionService;
use App\Domains\Transaction\Services\TransactionSplits;
use App\Http\Controllers\Controller;
use App\Http\Resources\MultiCurrencyTransactionResource;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Insane\Journal\Events\TransactionCreated;

class MultiCurrencyTransactionController extends Controller
{
    public function __construct(
        private MultiCurrencyTransactionService $multiCurrencyService
    ) {}

    /**
     * Create a transaction with multi-currency support
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', $this->teamRow($request, 'accounts')],
            'total' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
            'category_id' => ['nullable', $this->teamRow($request, 'categories')],
            'payee_id' => ['nullable', $this->teamRow($request, 'payees')],
            'counter_account_id' => ['nullable', $this->teamRow($request, 'accounts')],
            // DEPOSIT / WITHDRAW are what the model stores (the mobile app sends these);
            // credit / debit stay accepted for existing API clients.
            'direction' => 'required|in:credit,debit,DEPOSIT,WITHDRAW',
            'status' => 'nullable|in:draft,verified',
            ...TransactionSplits::rules($request->user()->current_team_id),
        ]);

        // A purchase split across categories: the lines carry the categories, the transaction itself has none.
        $items = [];
        if (! empty($validated['items'])) {
            $items = TransactionSplits::lines($validated['items'], (float) $validated['total'], $validated['payee_id'] ?? null);
            $validated['has_splits'] = true;
            $validated['category_id'] = null;
        }
        unset($validated['items']);

        $account = Account::where('team_id', $request->user()->current_team_id)->findOrFail($validated['account_id']);

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
                // Standard transaction in the account's own currency. The row alone moves nothing: the journal's
                // lines are what balances, the budget and reconciliations are summed from, so they are created
                // here the way the journal's own createTransaction() does (minus its look-alike lookup, which
                // would swallow a second identical purchase of the same day).
                $transaction = DB::transaction(function () use ($transactionData, $items) {
                    $data = Transaction::sanitizeData(['counter_account_id' => null, 'payee_id' => null, ...$transactionData]);
                    $transaction = Transaction::create($data);
                    $transaction->createLines($items, $data);

                    return $transaction;
                });
            }

            event(new TransactionCreated($transaction, $transactionData));

            return response()->json([
                'success' => true,
                'data' => new MultiCurrencyTransactionResource($transaction),
                'message' => 'Transaction created successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process a credit card payment with currency conversion
     */
    public function processPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', $this->teamRow($request, 'accounts')],
            'total' => 'required|numeric|min:0.01',
            'exchange_amount' => 'required|numeric|min:0.01',
            'secondary_currency' => 'required|string|size:3',
            'payment_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'category_id' => ['nullable', $this->teamRow($request, 'categories')],
            'payee_id' => ['nullable', $this->teamRow($request, 'payees')],
        ]);

        $account = Account::where('team_id', $request->user()->current_team_id)->findOrFail($validated['account_id']);

        // Check if user can create transactions for this account
        Gate::authorize('create', [Transaction::class, $account]);

        // Verify account supports multi-currency
        if (! $account->isMultiCurrency()) {
            return response()->json([
                'success' => false,
                'message' => 'Account does not support multi-currency transactions',
            ], 422);
        }

        // Verify account supports the secondary currency
        if (! $account->supportsCurrency($validated['secondary_currency'])) {
            return response()->json([
                'success' => false,
                'message' => "Account does not support currency {$validated['secondary_currency']}",
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
                'message' => 'Payment processed successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get currency balances for an account
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
                    'balances' => $balances,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve currency balances: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pending balances for an account (credit card specific)
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
                    'pending_balances' => $pendingBalances,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending balances: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single transaction with multi-currency information
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
                'data' => new MultiCurrencyTransactionResource($transaction),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transaction: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transactions with multi-currency information
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'nullable|exists:accounts,id',
            'currency_code' => 'nullable|string|size:3',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'month' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'uncategorized' => 'nullable|boolean',
            'status' => 'nullable|in:verified,draft,scheduled',
            'search' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        try {
            $query = Transaction::with(['account', 'counterAccount', 'category', 'payee'])
                ->where('team_id', $request->user()->current_team_id);

            // Filter by account if specified
            if (! empty($validated['account_id'])) {
                $query->where('account_id', $validated['account_id']);
            }

            // Filter by currency if specified
            if (! empty($validated['currency_code'])) {
                $query->where('currency_code', $validated['currency_code']);
            }

            // Filter by month (YYYY-MM)
            if (! empty($validated['month'])) {
                $monthStart = $validated['month'].'-01';
                $monthEnd = Carbon::parse($monthStart)->endOfMonth()->format('Y-m-d');
                $query->where('date', '>=', $monthStart)->where('date', '<=', $monthEnd);
            }

            // Filter by date range if specified
            if (! empty($validated['start_date'])) {
                $query->where('date', '>=', $validated['start_date']);
            }
            if (! empty($validated['end_date'])) {
                $query->where('date', '<=', $validated['end_date']);
            }

            // The app calls a not-yet-happened transaction "scheduled"; the database calls it "planned"
            if (! empty($validated['status'])) {
                $query->where('status', $validated['status'] === 'scheduled' ? Transaction::STATUS_PLANNED : $validated['status']);
            }

            // Free-text search over the description and the payee's name
            if (! empty($validated['search'])) {
                $term = '%'.addcslashes($validated['search'], '%_\\').'%';
                $query->where(function ($search) use ($term) {
                    $search->where('description', 'like', $term)
                        ->orWhereHas('payee', fn ($payee) => $payee->where('name', 'like', $term));
                });
            }

            // Uncategorized expenses filter (same criteria as budget summary.uncategorized)
            if (! empty($validated['uncategorized'])) {
                $query->uncategorizedExpenses();
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
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Row of the current team (or a global row with team_id 0).
     */
    private function teamRow(Request $request, string $table): Exists
    {
        return Rule::exists($table, 'id')->whereIn('team_id', [$request->user()->current_team_id, 0]);
    }
}
