<?php

namespace App\Mcp\Tools;

use App\Domains\Integration\Concerns\PlannedTransactionDTO;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\PayeeResolver;
use App\Domains\Transaction\Services\PlannedTransactionService;
use App\Mcp\Concerns\InteractsWithTeam;
use App\Models\Account;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Title('Create planned transaction')]
#[Name('create_planned_transaction')]
#[Description('Schedule a recurring bill or income, like one made in Loger\'s "Set recurrence" form ("la terapia de la niña, RD$12,000 cada 4 semanas desde el 1/12"). frequency is DAILY, WEEK_DAY (Monday to Friday), WEEK or MONTHLY and frequency_interval how many of them between payments (WEEK + 4 = every 4 weeks). It shows up in get_upcoming_payments and moves to its next date by itself when the real payment is recorded. Get account_id and category_id from list_accounts_and_categories first. Before calling, confirm the description, amount, account, first date and recurrence with the user. The reply lists the next dates so you can show them.')]
class CreatePlannedTransactionTool extends Tool
{
    use InteractsWithTeam;

    public const FREQUENCIES = ['DAILY', 'WEEK_DAY', 'WEEK', 'MONTHLY'];

    private const DUPLICATE_WINDOW_MINUTES = 2;

    private const NEXT_DATES = 3;

    public function handle(Request $request, PlannedTransactionService $planned): Response
    {
        if ($denied = $this->denyWithoutWriteAbility($request)) {
            return $denied;
        }

        $teamId = $this->teamId($request);
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'total' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'date' => ['required', 'date_format:Y-m-d'],
            'frequency' => ['required', Rule::in(self::FREQUENCIES)],
            'frequency_interval' => ['nullable', 'integer', 'min:1', 'max:52'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'],
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('team_id', $teamId)->whereNull('closed_at')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('team_id', $teamId)->where('resource_type', 'transactions')->whereNotNull('parent_id')],
            'payee_id' => ['nullable', 'integer', 'prohibits:payee_name', Rule::exists('payees', 'id')->where('team_id', $teamId)],
            'payee_name' => ['nullable', 'string', 'max:191'],
            'direction' => ['nullable', 'in:expense,income'],
            'currency_code' => ['nullable', 'string', 'size:3'],
        ], [
            'frequency.in' => 'frequency must be DAILY, WEEK_DAY, WEEK or MONTHLY.',
            'frequency_interval.min' => 'frequency_interval must be between 1 and 52.',
            'frequency_interval.max' => 'frequency_interval must be between 1 and 52.',
            'account_id.exists' => 'That account is not an open account of this household. Call list_accounts_and_categories for the ids.',
            'category_id.exists' => 'That category is not a budget category of this household. Call list_accounts_and_categories for the ids.',
            'payee_id.exists' => 'That payee is not in this household.',
            'payee_id.prohibits' => 'Send payee_id or payee_name, not both.',
        ]);

        $user = $request->user();
        $account = Account::query()->where('team_id', $teamId)->findOrFail($validated['account_id']);
        $direction = ($validated['direction'] ?? 'expense') === 'income' ? Transaction::DIRECTION_DEBIT : Transaction::DIRECTION_CREDIT;

        $duplicate = Transaction::query()
            ->where('team_id', $teamId)
            ->where('status', Transaction::STATUS_PLANNED)
            ->where('account_id', $account->id)
            ->where('direction', $direction)
            ->where('date', $validated['date'])
            ->where('total', $validated['total'])
            ->where('description', $validated['description'])
            ->where('created_at', '>=', now()->subMinutes(self::DUPLICATE_WINDOW_MINUTES))
            ->whereHas('schedule')
            ->latest('id')
            ->first();
        if ($duplicate) {
            return Response::json([...$this->present($duplicate, $planned), 'duplicate' => true]);
        }

        $transaction = DB::transaction(function () use ($validated, $teamId, $user, $account, $direction, $planned) {
            $payeeId = $validated['payee_id'] ?? null;
            if (filled($validated['payee_name'] ?? null)) {
                $payeeId = PayeeResolver::resolve(['team_id' => $teamId, 'user_id' => $user->id], trim($validated['payee_name']))->id;
            }

            return $planned->add(PlannedTransactionDTO::from([
                'team_id' => $teamId,
                'user_id' => $user->id,
                'account_id' => $account->id,
                'counter_account_id' => null,
                'payee_id' => $payeeId,
                'category_id' => $validated['category_id'] ?? null,
                'date' => $validated['date'],
                'end_date' => $validated['end_date'] ?? null,
                'end_type' => isset($validated['end_date']) ? 'DATE' : 'NEVER',
                'description' => $validated['description'],
                'total' => (float) $validated['total'],
                'currency_code' => strtoupper($validated['currency_code'] ?? $account->currency_code),
                'direction' => $direction,
                'frequency' => $validated['frequency'],
                'frequency_interval' => (int) ($validated['frequency_interval'] ?? 1),
                'resource_type_id' => 'MANUAL',
            ]), reuseCategoryPlan: false);
        });

        return Response::json([...$this->present(Transaction::query()->findOrFail($transaction->id), $planned), 'duplicate' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Transaction $transaction, PlannedTransactionService $planned): array
    {
        $transaction->loadMissing(['account:id,name', 'category:id,name', 'payee:id,name', 'schedule']);
        $schedule = $transaction->schedule;
        $date = substr((string) $transaction->date, 0, 10);
        $endDate = $schedule?->end_date?->format('Y-m-d');
        $interval = (int) ($schedule?->frequency_interval ?? 1);
        $nextDates = collect($planned->upcomingDates($date, (string) $schedule?->frequency, $interval, self::NEXT_DATES))
            ->filter(fn (string $next) => ! $endDate || $next <= $endDate)
            ->values();

        return [
            'id' => $transaction->id,
            'upcoming_payment_id' => "planned_{$transaction->id}",
            'description' => $transaction->description,
            'total' => round((float) (string) $transaction->total, 2),
            'currency_code' => $transaction->currency_code,
            'type' => $transaction->direction === Transaction::DIRECTION_DEBIT ? 'income' : 'expense',
            'account' => $transaction->account?->name,
            'category' => $transaction->category?->name,
            'payee' => $transaction->payee?->name,
            'frequency' => $schedule?->frequency,
            'frequency_interval' => $interval,
            'end_date' => $endDate,
            'next_dates' => $nextDates,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'description' => $schema->string()->description('What it is, e.g. "Terapia Sofía".')->required(),
            'total' => $schema->number()->description('Positive amount of each payment.')->required(),
            'date' => $schema->string()->description('First due date, YYYY-MM-DD.')->required(),
            'frequency' => $schema->string()->enum(self::FREQUENCIES)->description('DAILY, WEEK_DAY (Monday to Friday), WEEK or MONTHLY.')->required(),
            'frequency_interval' => $schema->integer()->min(1)->max(52)->description('How many frequency units between payments: 4 with WEEK = every 4 weeks. Default 1.'),
            'end_date' => $schema->string()->description('Last possible date, YYYY-MM-DD. Omit to repeat forever.'),
            'account_id' => $schema->integer()->description('Account the payment leaves from (or income arrives to).')->required(),
            'category_id' => $schema->integer()->description('Budget category.'),
            'payee_id' => $schema->integer()->description('Existing payee id.'),
            'payee_name' => $schema->string()->description('Payee by name, created if new. Instead of payee_id.'),
            'direction' => $schema->string()->enum(['expense', 'income'])->description('Default expense.'),
            'currency_code' => $schema->string()->description('ISO code. Defaults to the account currency.'),
        ];
    }
}
