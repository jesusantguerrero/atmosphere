<?php

namespace App\Mcp\Tools;

use App\Domains\AppCore\Models\Category;
use App\Mcp\Concerns\InteractsWithTeam;
use App\Models\Account;
use App\Models\Setting;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Accounts and categories')]
#[Name('list_accounts_and_categories')]
#[Description('The household open accounts with their balances, and its budget categories with their group. Call it to turn names like "Popular" or "Teléfono" into the ids other tools need.')]
#[IsReadOnly]
class ListAccountsAndCategoriesTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request): Response
    {
        $teamId = $this->teamId($request);

        $accounts = collect(Account::getByDetailTypes($teamId)->loadMissing('detailType'))
            ->reject(fn (Account $account) => $account->closed_at !== null)
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'balance' => round((float) (string) $account->balance, 2),
                'currency_code' => $account->currency_code,
                'type' => $account->detailType?->name,
            ])
            ->values();

        $categories = Category::query()
            ->where('team_id', $teamId)
            ->where('resource_type', 'transactions')
            ->whereNotNull('parent_id')
            ->with('group:id,name')
            ->orderBy('index')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'group' => $category->group?->name,
            ]);

        return Response::json([
            'currency_code' => strtoupper(trim((string) (Setting::getByTeam($teamId)['team_primary_currency_code'] ?? ''))) ?: 'DOP',
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
