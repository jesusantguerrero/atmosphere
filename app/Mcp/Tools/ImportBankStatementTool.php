<?php

namespace App\Mcp\Tools;

use App\Domains\Transaction\Services\StatementImportService;
use App\Mcp\Concerns\InteractsWithTeam;
use App\Models\Account;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Title('Import bank statement')]
#[Name('import_bank_statement')]
#[Description('Import the movements of a bank statement the user shared ("importa este estado de cuenta del BHD a Personal") as drafts in the Bandeja, for the user to review. Read the statement and send one row per movement: debit for money out, credit for money in, and keep the bank reference. BHD prints dates as dd/mm/yyyy (send YYYY-MM-DD) and amounts as RD$ (DOP) or US$ (USD); on a credit card statement a negative amount is a payment or refund, so send it as credit. Get account_id from list_accounts_and_categories and ask the user which account when it is not clear. Movements already in Loger are skipped, so importing the same statement twice is safe. Up to 500 rows per call.')]
class ImportBankStatementTool extends Tool
{
    use InteractsWithTeam;

    private const MAX_ROWS = 500;

    public function handle(Request $request, StatementImportService $statements): Response
    {
        if ($denied = $this->denyWithoutWriteAbility($request)) {
            return $denied;
        }

        $teamId = $this->teamId($request);
        $validated = $request->validate([
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('team_id', $teamId)->whereNull('closed_at')],
            'rows' => ['required', 'array', 'min:1', 'max:'.self::MAX_ROWS],
            'rows.*.date' => ['required', 'date_format:Y-m-d'],
            'rows.*.reference' => ['nullable', 'string', 'max:191'],
            'rows.*.description' => ['required', 'string', 'max:255'],
            'rows.*.debit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'rows.*.credit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'rows.*.currency' => ['nullable', 'string', 'size:3'],
        ], [
            'account_id.exists' => 'That account is not an open account of this household. Call list_accounts_and_categories for the ids.',
            'rows.max' => 'Send at most '.self::MAX_ROWS.' rows per call.',
        ]);

        $rows = [];
        foreach ($validated['rows'] as $index => $row) {
            $debit = (float) ($row['debit'] ?? 0);
            $credit = (float) ($row['credit'] ?? 0);
            if (($debit > 0) === ($credit > 0)) {
                return Response::error("Row {$index} needs either a debit (money out) or a credit (money in) greater than zero, not both.");
            }

            $rows[] = [
                'date' => $row['date'],
                'reference' => trim((string) ($row['reference'] ?? '')),
                'description' => $row['description'],
                'debit' => $debit,
                'credit' => $credit,
                'balance' => 0.0,
                ...(filled($row['currency'] ?? null) ? ['currency' => strtoupper($row['currency'])] : []),
            ];
        }

        $account = Account::query()->where('team_id', $teamId)->findOrFail($validated['account_id']);

        return Response::json([
            'account' => $account->name,
            ...$statements->importRows($rows, $account, $request->user()),
            'status' => 'draft',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'account_id' => $schema->integer()->description('Household account the statement belongs to.')->required(),
            'rows' => $schema->array()->items($schema->object([
                'date' => $schema->string()->description('YYYY-MM-DD.')->required(),
                'reference' => $schema->string()->description('Bank reference or confirmation number, as printed.'),
                'description' => $schema->string()->description('Movement description, as printed.')->required(),
                'debit' => $schema->number()->description('Money out (positive). 0 for a credit.'),
                'credit' => $schema->number()->description('Money in (positive). 0 for a debit.'),
                'currency' => $schema->string()->description('DOP or USD. Defaults to the account currency.'),
            ]))->description('One row per movement.')->required(),
        ];
    }
}
