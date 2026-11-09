<?php

namespace App\Http\Controllers\Api;

use App\Domains\Transaction\Services\StatementImportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ImportStatementRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Insane\Journal\Models\Core\AccountDetailType;

/**
 * Import a bank statement from the app's «+»: the same import as the web
 * account page. Movements land as drafts in the inbox (Bandeja).
 */
class MobileStatementImportController extends Controller
{
    public function import(ImportStatementRequest $request, StatementImportService $statements): JsonResponse
    {
        $teamId = $request->user()->current_team_id;

        // Statements carry no account number the parsers can read, so the app asks.
        if (! $request->validated('account_id')) {
            return response()->json([
                'needs_account' => true,
                'accounts' => Account::getByDetailTypes($teamId, AccountDetailType::ALL)
                    ->reject(fn (Account $account) => $account->closed_at !== null)
                    ->map(fn (Account $account) => ['id' => $account->id, 'name' => $account->name, 'currency_code' => $account->currency_code])
                    ->values(),
            ]);
        }

        $account = Account::query()->where('team_id', $teamId)->findOrFail($request->validated('account_id'));
        $this->authorize('update', $account);

        return response()->json([
            'needs_account' => false,
            'account_id' => $account->id,
            ...$statements->import($request->file('file'), $account, $request->user()),
            'status' => 'draft',
        ]);
    }
}
