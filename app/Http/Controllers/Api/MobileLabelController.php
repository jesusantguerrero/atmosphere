<?php

namespace App\Http\Controllers\Api;

use App\Domains\AppCore\Models\Label;
use App\Domains\Transaction\Models\TransactionLine;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Insane\Journal\Models\Core\TransactionLine as CoreTransactionLine;

class MobileLabelController extends Controller
{
    /**
     * The household's transaction labels (meal and product labels share the table).
     * Labels created from transactions often lose labelable_type (it is not
     * mass-assignable), so a label already used on a transaction line counts too.
     */
    public function index(Request $request): JsonResponse
    {
        $labels = Label::query()
            ->where('team_id', $request->user()->current_team_id)
            ->where(fn ($query) => $query
                ->whereIn('labelable_type', [TransactionLine::class, CoreTransactionLine::class])
                ->orWhereExists(fn ($used) => $used->selectRaw('1')
                    ->from('label_resource')
                    ->join('transaction_lines', 'transaction_lines.id', '=', 'label_resource.resource_id')
                    ->whereColumn('label_resource.label_id', 'labels.id')))
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return response()->json(['data' => $labels]);
    }
}
