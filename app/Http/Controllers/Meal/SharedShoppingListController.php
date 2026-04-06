<?php

namespace App\Http\Controllers\Meal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;

class SharedShoppingListController extends Controller
{
    public function show(string $token): View
    {
        $plan = Plan::where('share_token', $token)->first();

        if (! $plan) {
            abort(404);
        }

        $stages = $plan->stages->map(function ($stage) {
            return [
                'id' => $stage->id,
                'name' => $stage->name,
                'items' => $stage->items()->orderBy('order')->get()->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'is_done' => (bool) $item->is_done,
                ]),
            ];
        });

        return view('shared-shopping-list', [
            'plan' => $plan,
            'stages' => $stages,
            'token' => $token,
        ]);
    }

    public function toggleItem(Request $request, string $token, int $itemId): JsonResponse
    {
        $plan = Plan::where('share_token', $token)->first();

        if (! $plan) {
            return response()->json(['error' => 'List not found.'], 404);
        }

        $item = PlanItem::where('id', $itemId)
            ->whereHas('stage', fn ($q) => $q->where('plan_id', $plan->id))
            ->first();

        if (! $item) {
            return response()->json(['error' => 'Item not found.'], 404);
        }

        $item->is_done = ! $item->is_done;
        $item->saveQuietly();

        return response()->json(['is_done' => (bool) $item->is_done]);
    }
}
