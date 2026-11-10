<?php

namespace App\Mcp\Tools;

use App\Domains\Transaction\Models\Transaction;
use App\Mcp\Concerns\InteractsWithTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('List transactions')]
#[Name('list_transactions')]
#[Description('Search the household transactions, newest first. Filter by month or date range, category, account, direction or free text over the description and payee ("¿cuándo pagué Claro?").')]
#[IsReadOnly]
class ListTransactionsTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'category_id' => ['nullable', 'integer'],
            'account_id' => ['nullable', 'integer'],
            'direction' => ['nullable', 'in:expense,income'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Transaction::query()
            ->with(['account:id,name', 'category:id,name', 'payee:id,name'])
            ->where('team_id', $this->teamId($request))
            ->when($validated['month'] ?? null, function ($query, string $month) use ($request) {
                [$start, $end] = $this->monthRange($request, $month);
                $query->whereBetween('date', [$start, $end]);
            })
            ->when($validated['start_date'] ?? null, fn ($query, string $date) => $query->where('date', '>=', $date))
            ->when($validated['end_date'] ?? null, fn ($query, string $date) => $query->where('date', '<=', $date))
            ->when($validated['category_id'] ?? null, fn ($query, int $id) => $query->where('category_id', $id))
            ->when($validated['account_id'] ?? null, fn ($query, int $id) => $query->where('account_id', $id))
            ->when($validated['direction'] ?? null, fn ($query, string $direction) => $query->where('direction', $direction === 'income' ? Transaction::DIRECTION_DEBIT : Transaction::DIRECTION_CREDIT))
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($inner) => $inner->where('description', 'like', $term)
                    ->orWhereHas('payee', fn ($payee) => $payee->where('name', 'like', $term)));
            });

        $page = $query->orderByDesc('date')->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 20, ['*'], 'page', $validated['page'] ?? 1);

        return Response::json([
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
            'items' => collect($page->items())->map(fn (Transaction $transaction) => self::present($transaction))->values(),
        ]);
    }

    /**
     * The shape the MCP tools use to show a transaction.
     *
     * @return array<string, mixed>
     */
    public static function present(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'date' => substr((string) $transaction->date, 0, 10),
            'description' => $transaction->description,
            'payee' => $transaction->payee?->name,
            'category' => $transaction->category?->name,
            'account' => $transaction->account?->name,
            'total' => round((float) (string) $transaction->total, 2),
            'direction' => $transaction->direction === Transaction::DIRECTION_DEBIT ? 'income' : ($transaction->direction === Transaction::DIRECTION_CREDIT ? 'expense' : 'entry'),
            'currency_code' => $transaction->currency_code,
            'status' => $transaction->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'month' => $schema->string()->description('Month as YYYY-MM.'),
            'start_date' => $schema->string()->description('From this date, YYYY-MM-DD.'),
            'end_date' => $schema->string()->description('Up to this date, YYYY-MM-DD.'),
            'category_id' => $schema->integer()->description('Category id from list_accounts_and_categories.'),
            'account_id' => $schema->integer()->description('Account id from list_accounts_and_categories.'),
            'direction' => $schema->string()->enum(['expense', 'income']),
            'search' => $schema->string()->description('Text to find in the description or payee name.'),
            'page' => $schema->integer()->min(1),
            'per_page' => $schema->integer()->min(1)->max(50)->description('Default 20.'),
        ];
    }
}
