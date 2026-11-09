<?php

namespace App\Mcp\Tools;

use App\Domains\Journal\Actions\TransactionCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Mcp\Concerns\InteractsWithTeam;
use App\Models\Account;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Insane\Journal\Models\Core\Payee;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Title('Create transaction')]
#[Name('create_transaction')]
#[Description('Record a verified expense or income ("registra RD$2,400 de Claro en Teléfono"). Get account_id and category_id from list_accounts_and_categories first; an expense needs a category. The same transaction sent twice within two minutes is not duplicated.')]
class CreateTransactionTool extends Tool
{
    use InteractsWithTeam;

    private const DUPLICATE_WINDOW_MINUTES = 2;

    public function handle(Request $request, TransactionCreate $creator): Response
    {
        if ($denied = $this->denyWithoutWriteAbility($request)) {
            return $denied;
        }

        $teamId = $this->teamId($request);
        $validated = $request->validate([
            'type' => ['required', 'in:expense,income'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'description' => ['required', 'string', 'max:255'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('team_id', $teamId)->whereNull('closed_at')],
            'category_id' => ['required_if:type,expense', 'nullable', 'integer', Rule::exists('categories', 'id')->where('team_id', $teamId)->where('resource_type', 'transactions')->whereNotNull('parent_id')],
            'payee_name' => ['nullable', 'string', 'max:191'],
        ], [
            'category_id.required_if' => 'An expense needs a category_id.',
        ]);

        $user = $request->user();
        $account = Account::query()->where('team_id', $teamId)->findOrFail($validated['account_id']);
        $date = $validated['date'] ?? $this->today($request)->format('Y-m-d');
        $direction = $validated['type'] === 'income' ? Transaction::DIRECTION_DEBIT : Transaction::DIRECTION_CREDIT;

        $duplicate = Transaction::query()
            ->where('team_id', $teamId)
            ->where('account_id', $account->id)
            ->where('direction', $direction)
            ->where('date', $date)
            ->where('total', $validated['amount'])
            ->where('description', $validated['description'])
            ->where('created_at', '>=', now()->subMinutes(self::DUPLICATE_WINDOW_MINUTES))
            ->latest('id')
            ->first();
        if ($duplicate) {
            return Response::json([...$this->present($duplicate), 'duplicate' => true]);
        }

        $payeeId = filled($validated['payee_name'] ?? null)
            ? Payee::firstOrCreate(['team_id' => $teamId, 'name' => trim($validated['payee_name'])], ['user_id' => $user->id])->id
            : null;

        $transaction = $creator->create($user, [
            'account_id' => $account->id,
            'counter_account_id' => null,
            'date' => $date,
            'description' => $validated['description'],
            'total' => $validated['amount'],
            'currency_code' => $account->currency_code,
            'direction' => $direction,
            'status' => Transaction::STATUS_VERIFIED,
            'category_id' => $validated['category_id'] ?? null,
            'payee_id' => $payeeId,
        ]);

        return Response::json([...$this->present(Transaction::query()->findOrFail($transaction->id)), 'duplicate' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Transaction $transaction): array
    {
        $transaction->loadMissing(['account:id,name', 'category:id,name', 'payee:id,name']);

        return [
            'id' => $transaction->id,
            'date' => substr((string) $transaction->date, 0, 10),
            'description' => $transaction->description,
            'total' => round((float) (string) $transaction->total, 2),
            'type' => $transaction->direction === Transaction::DIRECTION_DEBIT ? 'income' : 'expense',
            'account' => $transaction->account?->name,
            'category' => $transaction->category?->name,
            'payee' => $transaction->payee?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(['expense', 'income'])->required(),
            'amount' => $schema->number()->description('Positive amount in the account currency.')->required(),
            'description' => $schema->string()->description('What it was, e.g. "Claro internet".')->required(),
            'date' => $schema->string()->description('YYYY-MM-DD. Defaults to today in the household time zone.'),
            'account_id' => $schema->integer()->description('Account the money left from or arrived to.')->required(),
            'category_id' => $schema->integer()->description('Budget category. Required for expenses.'),
            'payee_name' => $schema->string()->description('Who was paid or who paid. Created if new.'),
        ];
    }
}
