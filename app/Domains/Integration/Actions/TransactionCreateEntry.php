<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Integration\Concerns\TransactionDataDTO;
use App\Domains\Transaction\Models\TransactionLine;
use App\Domains\Transaction\Services\TransactionService;
use App\Helpers\FormulaHelper;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\EntryGenerated;
use App\Domains\Transaction\Services\PayeeResolver;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Payee;
use Insane\Journal\Models\Core\Transaction;

class TransactionCreateEntry implements AutomationActionContract
{
    use TransactionAction;

    public static function handle(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    ) {
        $taskData = json_decode($task->values);

        $accountId = FormulaHelper::parseFormula($taskData->account_id, $payload);
        $payee = null;
        $payeeId = null;
        $counterAccountId = null;
        $transactionCategoryId = null;

        if ($payeeName = $payload['payee']) {
            $payee = PayeeResolver::resolve($automation, $payeeName ?? 'General Provider');
            $lastTransaction = TransactionLine::where([
                'payee_id' => $payee->id,
                'team_id' => $automation->team_id,
            ])->first();
            $transactionCategoryId = $lastTransaction?->category_id;
            $payeeId = $payee->id;
            $counterAccountId = $payee->account_id;
        }

        $description = FormulaHelper::parseFormula($taskData?->description, $payload);

        if ($payload['productCode'] ?? false) {
            $account = Account::where('team_id', $automation->team_id)
                ->where('number', $payload['productCode'])
                ->whereRaw('name like ?', ['%'.$payload['productBrand'].'%'])
                ->first();

            if ($account) {
                $accountId = $account->id;
            }
        }

        $date = FormulaHelper::parseFormula($taskData?->date, $payload);
        $settings = Setting::getByTeam($automation->team_id);
        $currencyCode = self::resolveCurrencyCode(
            FormulaHelper::parseFormula($taskData?->currency_code, $payload),
            $settings
        );

        $transactionData = [
            'team_id' => $automation->team_id,
            'user_id' => $automation->user_id,
            'account_id' => $accountId,
            'payee_id' => $payee ? $payeeId : null,
            'payee_name' => $payee ? $payee->name : null,
            'counter_account_id' => $counterAccountId ?? null,
            'date' => $date,
            'currency_code' => $currencyCode,
            'category_id' => $transactionCategoryId ?? null,
            'description' => $description,
            'reference' => $payload['messageId'] ?? ("{$payload['id']}:{$description}:{$date}") ?? null,
            'direction' => FormulaHelper::parseFormula($taskData?->direction, $payload),
            'total' => FormulaHelper::parseFormula($taskData?->total, $payload),
            'items' => [],
            'meta_data' => json_encode([
                'resource_id' => $payload['id'],
                'resource_origin' => $previousTask->name,
                'resource_type' => $trigger->name,
                'resource_description' => $description,
                ...($payload['productName'] ? ['product_name' => $payload['productName']] : []),
                ...($payload['productCode'] ? ['product_code' => $payload['productCode']] : []),
                ...($payload['productBrand'] ? ['product_brand' => $payload['productBrand']] : []),
                ...(($payload['destinationProductName'] ?? null) ? ['destination_product_name' => $payload['destinationProductName']] : []),
                ...(($payload['destinationProductCode'] ?? null) ? ['destination_product_code' => $payload['destinationProductCode']] : []),
            ]),
        ];

        if ($destinationAccount = self::resolveInternalTransferAccount($payload, $accountId, $automation->team_id)) {
            $transactionData['is_transfer'] = true;
            $transactionData['counter_account_id'] = $destinationAccount->id;
            $transactionData['payee_id'] = null;
            $transactionData['payee_name'] = null;
            $transactionData['category_id'] = null;
            $transactionData['description'] = 'Transferencia a '.$destinationAccount->name;
        }

        if ($cashAccountId = self::resolveCashWithdrawalAccountId($payload, $accountId, $settings)) {
            $transactionData['is_transfer'] = true;
            $transactionData['counter_account_id'] = $cashAccountId;
            $transactionData['payee_id'] = null;
            $transactionData['payee_name'] = null;
            $transactionData['category_id'] = null;
        }

        // Auto-confirm transactions for credit card accounts (email is the confirmation)
        $account = Account::find($accountId);
        if ($account && $account->credit_closing_day) {
            $transactionData['status'] = Transaction::STATUS_VERIFIED;
        }

        $transactionService = new TransactionService;

        if ($transaction = $transactionService->findIfDuplicated($transactionData)) {
            return $transaction;
        }

        $transaction = Transaction::createTransaction($transactionData);

        /**
         * Dedupe import notifications: an automation that ingests a batch
         * (email/PDF/CSV import) creates one transaction at a time and
         * previously fired one EntryGenerated per row — meaning a single
         * BHD statement import spammed the bell with dozens of identical
         * "New transactions have been imported" notifications. Skip if the
         * user already has an unread EntryGenerated waiting: one nudge is
         * enough. When they clear the unread queue, the next import batch
         * gets a fresh notification.
         */
        $user = User::find($automation->user_id);
        $alreadyNotified = $user->unreadNotifications()
            ->where('type', EntryGenerated::class)
            ->exists();

        if (! $alreadyNotified) {
            $user->notify(new EntryGenerated($transaction));
        }

        return $transaction;
    }

    /**
     * Resolve the currency for the created transaction.
     *
     * The formula-resolved value wins when it is a valid 3-letter code. When the
     * automation task references a variable the parser didn't provide, the
     * formula collapses to an empty string or "0"; in that case we fall back to
     * the team's primary currency instead of silently defaulting to USD.
     *
     * @param  array<string, mixed>  $settings
     */
    private static function resolveCurrencyCode(?string $resolved, array $settings): string
    {
        $resolved = trim((string) $resolved);

        if (preg_match('/^[A-Za-z]{3}$/', $resolved)) {
            return strtoupper($resolved);
        }

        return $settings['team_primary_currency_code'] ?? 'USD';
    }

    /**
     * Return the configured cash account when this email is a cash withdrawal
     * that should be routed there as a transfer, or null to keep the default
     * expense behavior.
     *
     * @param  array<string, mixed>  $settings
     */
    private static function resolveCashWithdrawalAccountId(mixed $payload, int|string|null $sourceAccountId, array $settings): ?int
    {
        if (($payload['transactionType'] ?? null) !== TransactionDataDTO::TYPE_CASH_WITHDRAWAL) {
            return null;
        }

        $cashAccountId = $settings['team_cash_withdrawal_account_id'] ?? null;

        if (! $cashAccountId || (int) $cashAccountId === (int) $sourceAccountId) {
            return null;
        }

        return (int) $cashAccountId;
    }

    /**
     * Match a BHD "Transacciones entre mis productos" destination to one of the
     * team's accounts using the destination product's visible last digits.
     */
    private static function resolveInternalTransferAccount(
        mixed $payload,
        int|string|null $sourceAccountId,
        int $teamId
    ): ?Account {
        if (($payload['transactionType'] ?? null) !== TransactionDataDTO::TYPE_INTERNAL_TRANSFER) {
            return null;
        }

        $destinationProductCode = $payload['destinationProductCode'] ?? null;
        if (! $destinationProductCode) {
            return null;
        }

        $destinationAccount = Account::where('team_id', $teamId)
            ->where('number', $destinationProductCode)
            ->when(
                $payload['productBrand'] ?? null,
                fn ($query, $brand) => $query->whereRaw('name like ?', ['%'.$brand.'%'])
            )
            ->first();

        if (! $destinationAccount || (int) $destinationAccount->id === (int) $sourceAccountId) {
            return null;
        }

        return $destinationAccount;
    }

    public function getName(): string
    {
        return 'createTransaction';
    }

    public function label(): string
    {
        return 'Create transaction';
    }

    public function getDescription(): string
    {
        return 'Create a new transaction';
    }
}
