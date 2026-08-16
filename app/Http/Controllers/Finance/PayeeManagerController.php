<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayeeAlias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Payee;

/**
 * Manage payees (beneficiarios): list with transaction counts, rename, merge
 * duplicates, and delete. Payees are team-scoped and auto-created from
 * transactions, so case/whitespace duplicates are common — merge reassigns
 * every reference (header `transactions.payee_id` AND split
 * `transaction_lines.payee_id`) to the target, then deletes the source.
 * System/transfer payees (user_id 0) are hidden from the manager.
 */
class PayeeManagerController extends Controller
{
    public function index(Request $request)
    {
        $teamId = $request->user()->current_team_id;

        $payees = Payee::query()
            ->where('team_id', $teamId)
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', '>', 0);
            })
            ->select('id', 'name')
            ->selectRaw(
                '(select count(*) from transactions where transactions.payee_id = payees.id and transactions.team_id = ?) as transactions_count',
                [$teamId]
            )
            ->orderByDesc('transactions_count')
            ->orderBy('name')
            ->get();

        return inertia('Finance/Payees', [
            'payees' => $payees,
        ]);
    }

    public function update(Request $request, int $payee)
    {
        $teamId = $request->user()->current_team_id;
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        Payee::where('team_id', $teamId)->findOrFail($payee)
            ->update(['name' => trim($data['name'])]);

        return back();
    }

    public function merge(Request $request, int $payee)
    {
        $teamId = $request->user()->current_team_id;
        $data = $request->validate([
            'target_id' => ['required', 'integer'],
        ]);

        // Hard guard: never merge a payee into itself (would delete the payee
        // its own transactions were just reassigned to).
        if ((int) $data['target_id'] === $payee) {
            return back();
        }

        $source = Payee::where('team_id', $teamId)->findOrFail($payee);
        $target = Payee::where('team_id', $teamId)->findOrFail($data['target_id']);

        DB::transaction(function () use ($source, $target, $teamId) {
            DB::table('transactions')
                ->where('team_id', $teamId)->where('payee_id', $source->id)
                ->update(['payee_id' => $target->id]);
            DB::table('transaction_lines')
                ->where('team_id', $teamId)->where('payee_id', $source->id)
                ->update(['payee_id' => $target->id]);

            // Keep any aliases that pointed at the source pointing at the target,
            // and add the source's name as an alias so future imports of that
            // name resolve to the target instead of re-creating the duplicate.
            PayeeAlias::where('team_id', $teamId)->where('payee_id', $source->id)
                ->update(['payee_id' => $target->id]);
            PayeeAlias::updateOrCreate(
                ['team_id' => $teamId, 'name' => $source->name],
                ['payee_id' => $target->id]
            );

            $source->delete();
        });

        return back();
    }

    public function destroy(Request $request, int $payee)
    {
        $teamId = $request->user()->current_team_id;
        $model = Payee::where('team_id', $teamId)->findOrFail($payee);

        DB::transaction(function () use ($model, $teamId) {
            DB::table('transactions')
                ->where('team_id', $teamId)->where('payee_id', $model->id)
                ->update(['payee_id' => null]);
            DB::table('transaction_lines')
                ->where('team_id', $teamId)->where('payee_id', $model->id)
                ->update(['payee_id' => null]);

            // Drop any aliases that resolved to this payee.
            PayeeAlias::where('team_id', $teamId)->where('payee_id', $model->id)->delete();

            $model->delete();
        });

        return back();
    }
}
