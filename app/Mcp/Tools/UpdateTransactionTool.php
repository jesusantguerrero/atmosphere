<?php

namespace App\Mcp\Tools;

use App\Domains\Journal\Actions\TransactionUpdate;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\PayeeResolver;
use App\Mcp\Concerns\InteractsWithTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Title('Update transaction')]
#[Name('update_transaction')]
#[Description('Change one transaction of the household: fix its date, description, amount, account, category, payee or notes, approve a draft from the Bandeja (status verified), or turn a payment into a transfer to another account ("pon el pago de Qik como transferencia a la tarjeta": counter_account_id = the card). Find transaction_id with list_transactions and the other ids with list_accounts_and_categories. Send only the fields that change; the rest keep their value. Before calling, show the user the transaction as it is and as it will be, and wait for their confirmation. A transfer has no category or payee: send counter_account_id null to turn it back into an expense or income. Split transactions cannot be edited here.')]
class UpdateTransactionTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request, TransactionUpdate $updater): Response
    {
        if ($denied = $this->denyWithoutWriteAbility($request)) {
            return $denied;
        }

        $teamId = $this->teamId($request);
        $openAccount = Rule::exists('accounts', 'id')->where('team_id', $teamId)->whereNull('closed_at');
        $validated = $request->validate([
            'transaction_id' => ['required', 'integer', Rule::exists('transactions', 'id')->where('team_id', $teamId)->whereNull('deleted_at')],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'description' => ['sometimes', 'string', 'max:255'],
            'total' => ['sometimes', 'numeric', 'gt:0', 'max:999999999.99'],
            'account_id' => ['sometimes', 'integer', $openAccount],
            'counter_account_id' => ['sometimes', 'nullable', 'integer', $openAccount],
            'category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('categories', 'id')->where('team_id', $teamId)->where('resource_type', 'transactions')->whereNotNull('parent_id')],
            'payee_id' => ['sometimes', 'nullable', 'integer', 'prohibits:payee_name', Rule::exists('payees', 'id')->where('team_id', $teamId)],
            'payee_name' => ['sometimes', 'string', 'max:191'],
            'status' => ['sometimes', 'in:'.Transaction::STATUS_DRAFT.','.Transaction::STATUS_VERIFIED],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ], [
            'transaction_id.exists' => 'That transaction is not in this household. Call list_transactions for the ids.',
            'account_id.exists' => 'That account is not an open account of this household. Call list_accounts_and_categories for the ids.',
            'counter_account_id.exists' => 'That counter account is not an open account of this household. Call list_accounts_and_categories for the ids.',
            'category_id.exists' => 'That category is not a budget category of this household. Call list_accounts_and_categories for the ids.',
            'payee_id.exists' => 'That payee is not in this household.',
            'payee_id.prohibits' => 'Send payee_id or payee_name, not both.',
        ]);

        $transaction = Transaction::query()->where('team_id', $teamId)->findOrFail($validated['transaction_id']);
        $changes = collect($validated)->except('transaction_id');
        if ($changes->isEmpty()) {
            return Response::error('Nothing to change: send at least one field besides transaction_id.');
        }
        if ($transaction->has_splits) {
            return Response::error('This transaction is split across categories; edit it in Loger instead.');
        }

        $accountId = (int) $changes->get('account_id', $transaction->account_id);
        $isTransfer = $changes->has('counter_account_id') ? $changes->get('counter_account_id') !== null : (bool) $transaction->is_transfer;
        $changesPayee = $changes->has('payee_id') || $changes->has('payee_name');

        if ($isTransfer) {
            if ($changes->has('category_id') || $changesPayee) {
                return Response::error('A transfer has no category or payee. Send counter_account_id null to turn it into an expense or income first.');
            }
            $counterAccountId = (int) $changes->get('counter_account_id', $transaction->counter_account_id);
            if ($counterAccountId === $accountId) {
                return Response::error('The counter account must be a different account than the transaction account.');
            }
        }

        $data = DB::transaction(function () use ($request, $transaction, $changes, $teamId, $accountId, $isTransfer, $changesPayee, $updater) {
            $payeeId = $changes->get('payee_id', $transaction->payee_id);
            if (filled($changes->get('payee_name'))) {
                $payeeId = PayeeResolver::resolve(['team_id' => $teamId, 'user_id' => $request->user()->id], trim($changes->get('payee_name')))->id;
            }

            $data = [
                'account_id' => $accountId,
                'date' => $changes->get('date', substr((string) $transaction->date, 0, 10)),
                'description' => $changes->get('description', $transaction->description),
                'total' => $changes->get('total', $transaction->total),
                'currency_code' => $transaction->currency_code,
                'direction' => $transaction->direction,
                'status' => $changes->get('status', $transaction->status),
                'notes' => $changes->get('notes', $transaction->notes),
                ...($isTransfer ? [
                    'is_transfer' => true,
                    'counter_account_id' => (int) $changes->get('counter_account_id', $transaction->counter_account_id),
                    'category_id' => null,
                    'payee_id' => null,
                ] : [
                    'is_transfer' => false,
                    'counter_account_id' => $transaction->is_transfer || $changesPayee || ! $payeeId ? null : $transaction->counter_account_id,
                    'category_id' => $changes->get('category_id', $transaction->is_transfer ? null : $transaction->category_id),
                    'payee_id' => $payeeId,
                ]),
            ];

            $updater->update($request->user(), $transaction, $data);

            return $data;
        });

        $transaction = Transaction::query()->with(['account:id,name', 'category:id,name', 'payee:id,name', 'counterAccount:id,name'])->findOrFail($transaction->id);

        return Response::json([
            ...ListTransactionsTool::present($transaction),
            'is_transfer' => (bool) $data['is_transfer'],
            'counter_account' => $data['is_transfer'] ? $transaction->counterAccount?->name : null,
            'notes' => $transaction->notes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'transaction_id' => $schema->integer()->description('Transaction id from list_transactions.')->required(),
            'date' => $schema->string()->description('New date, YYYY-MM-DD.'),
            'description' => $schema->string()->description('New description.'),
            'total' => $schema->number()->description('New positive amount in the transaction currency.'),
            'account_id' => $schema->integer()->description('Account the money left from or arrived to.'),
            'counter_account_id' => $schema->integer()->description('Makes it a transfer to this account (e.g. paying a credit card from the bank). null turns a transfer back into an expense or income.'),
            'category_id' => $schema->integer()->description('Budget category. null leaves it uncategorized.'),
            'payee_id' => $schema->integer()->description('Existing payee id.'),
            'payee_name' => $schema->string()->description('Payee by name, created if new. Instead of payee_id.'),
            'status' => $schema->string()->enum([Transaction::STATUS_DRAFT, Transaction::STATUS_VERIFIED])->description('verified approves a draft from the Bandeja.'),
            'notes' => $schema->string()->description('Free notes.'),
        ];
    }
}
