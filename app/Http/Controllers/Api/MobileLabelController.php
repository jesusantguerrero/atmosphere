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
     */
    public function index(Request $request): JsonResponse
    {
        $labels = Label::query()
            ->where('team_id', $request->user()->current_team_id)
            ->whereIn('labelable_type', [TransactionLine::class, CoreTransactionLine::class])
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return response()->json(['data' => $labels]);
    }
}
