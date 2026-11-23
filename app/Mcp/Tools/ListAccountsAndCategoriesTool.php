<?php

namespace App\Mcp\Tools;

use App\Domains\AppCore\Models\Category;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Mcp\Concerns\InteractsWithTeam;
use App\Models\Account;
use App\Models\Setting;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Accounts and categories')]
#[Name('list_accounts_and_categories')]
#[Description('The household open accounts with their balances, its budget categories with their group, and its profiles (the people in it). Call it to turn names like "Popular", "Teléfono" or "Dianita" into the ids other tools need. A credit card with a cut day also has card: its closing day, limit and available credit, the statement a purchase made today lands in (closes_on, due_on) and the one after it, so "si compro hoy, ¿cuándo lo pago?" is answered without asking.')]
#[IsReadOnly]
class ListAccountsAndCategoriesTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request): Response
    {
        $teamId = $this->teamId($request);
        $today = $this->today($request);

        $accounts = collect(Account::getByDetailTypes($teamId)->loadMissing('detailType'))
            ->reject(fn (Account $account) => $account->closed_at !== null)
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'balance' => round((float) (string) $account->balance, 2),
                'currency_code' => $account->currency_code,
                'type' => $account->detailType?->name,
                ...($account->credit_closing_day ? ['card' => $this->card($account, $today)] : []),
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

        $profiles = LogerProfile::query()
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (LogerProfile $profile) => ['id' => $profile->id, 'name' => $profile->name]);

        return Response::json([
            'currency_code' => strtoupper(trim((string) (Setting::getByTeam($teamId)['team_primary_currency_code'] ?? ''))) ?: 'DOP',
            'accounts' => $accounts,
            'categories' => $categories,
            'profiles' => $profiles,
        ]);
    }

    /**
     * A purchase on the cut day still closes in that statement, like NextPaymentsService counts it.
     *
     * @return array{closing_day: int, payment_days: int, credit_limit: ?float, available_credit: ?float, current_statement: array{closes_on: string, due_on: string}, next_statement: array{starts_on: string, closes_on: string, due_on: string}}
     */
    private function card(Account $account, Carbon $today): array
    {
        $cutIn = fn (Carbon $month) => $month->copy()->startOfMonth()->day(min((int) $account->credit_closing_day, $month->daysInMonth));
        $closesOn = $today->lte($cutIn($today)) ? $cutIn($today) : $cutIn($today->copy()->startOfMonth()->addMonthNoOverflow());
        $nextClosesOn = $cutIn($closesOn->copy()->startOfMonth()->addMonthNoOverflow());
        $limit = (float) $account->credit_limit > 0 ? round((float) $account->credit_limit, 2) : null;

        return [
            'closing_day' => (int) $account->credit_closing_day,
            'payment_days' => (int) ($account->credit_payment_days ?? 0),
            'credit_limit' => $limit,
            'available_credit' => $limit !== null ? round($limit + min(0, (float) (string) $account->balance), 2) : null,
            'current_statement' => [
                'closes_on' => $closesOn->format('Y-m-d'),
                'due_on' => $account->paymentDueDateForCut($closesOn)->format('Y-m-d'),
            ],
            'next_statement' => [
                'starts_on' => $closesOn->copy()->addDay()->format('Y-m-d'),
                'closes_on' => $nextClosesOn->format('Y-m-d'),
                'due_on' => $account->paymentDueDateForCut($nextClosesOn)->format('Y-m-d'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
