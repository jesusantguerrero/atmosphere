<?php

namespace App\Http\Controllers\Meal;

use App\Domains\Meal\Models\MealMenu;
use App\Domains\Meal\Models\MealPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MealMenuController
{
    public function index(Request $request): JsonResponse
    {
        $menus = MealMenu::where('team_id', $request->user()->current_team_id)
            ->withCount('mealPlans')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($menus);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $user = $request->user();

        $menu = MealMenu::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'is_template' => true,
        ]);

        MealPlan::where('team_id', $user->current_team_id)
            ->whereBetween('date', [$request->start_date, $request->end_date.' 23:59:59'])
            ->update(['menu_id' => $menu->id]);

        return redirect()->back()->with('flash', [
            'message' => "Menu \"{$menu->name}\" saved successfully.",
            'type' => 'success',
        ]);
    }

    public function load(Request $request, MealMenu $menu): RedirectResponse
    {
        $request->validate([
            'target_start_date' => ['required', 'date'],
        ]);

        $this->authorizeTeamAccess($request, $menu);

        $sourcePlans = $menu->mealPlans()->orderBy('date')->get();

        if ($sourcePlans->isEmpty()) {
            return redirect()->back()->with('flash', [
                'message' => 'This menu has no meals to load.',
                'type' => 'warning',
            ]);
        }

        $user = $request->user();
        $sourceStartDate = Carbon::parse($sourcePlans->first()->date)->startOfDay();
        $targetStartDate = Carbon::parse($request->target_start_date)->startOfDay();

        foreach ($sourcePlans as $sourcePlan) {
            $dayOffset = Carbon::parse($sourcePlan->date)->startOfDay()->diffInDays($sourceStartDate);
            $newDate = $targetStartDate->copy()->addDays($dayOffset);

            MealPlan::create([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id,
                'meal_id' => $sourcePlan->meal_id,
                'meal_type_id' => $sourcePlan->meal_type_id,
                'date' => $newDate->toDateTimeString(),
                'name' => $sourcePlan->name,
                'menu_id' => $menu->id,
            ]);
        }

        return redirect()->back()->with('flash', [
            'message' => "Menu \"{$menu->name}\" loaded into the selected week.",
            'type' => 'success',
        ]);
    }

    public function destroy(Request $request, MealMenu $menu): RedirectResponse
    {
        $this->authorizeTeamAccess($request, $menu);

        $menu->mealPlans()->update(['menu_id' => null]);
        $menu->delete();

        return redirect()->back()->with('flash', [
            'message' => 'Menu deleted.',
            'type' => 'success',
        ]);
    }

    private function authorizeTeamAccess(Request $request, MealMenu $menu): void
    {
        abort_if($menu->team_id !== $request->user()->current_team_id, 403);
    }
}
