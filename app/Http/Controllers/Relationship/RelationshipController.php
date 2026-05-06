<?php

namespace App\Http\Controllers\Relationship;

use App\Domains\Housing\Models\Occurrence;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RelationshipController extends Controller
{
    public function index(Request $request): Response
    {
        $teamId = $request->user()->current_team_id;

        $relationships = Occurrence::query()
            ->byTeam($teamId)
            ->ofType(Occurrence::TYPE_RELATIONSHIP)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Occurrence $occurrence) => [
                'id' => $occurrence->id,
                'name' => $occurrence->name,
                'last_date' => $occurrence->last_date?->format('Y-m-d'),
                'avg_days_passed' => $occurrence->avg_days_passed,
                'days_until_next' => $occurrence->daysUntilNext(),
            ]);

        return Inertia::render('Relationships/Index', [
            'relationships' => $relationships,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'avg_days_passed' => ['required', 'integer', 'min:1'],
        ]);

        $teamId = $request->user()->current_team_id;

        Occurrence::create([
            'team_id' => $teamId,
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'type' => Occurrence::TYPE_RELATIONSHIP,
            'avg_days_passed' => $validated['avg_days_passed'],
            'is_active' => true,
            'notify_on_avg' => true,
        ]);

        return redirect()->back();
    }

    public function markAsOccurred(Request $request, Occurrence $occurrence): RedirectResponse
    {
        $today = now()->startOfDay();
        $previousLastDate = $occurrence->last_date;

        $newAvgDays = $occurrence->avg_days_passed;

        if ($previousLastDate) {
            $daysSinceLast = (int) $previousLastDate->diffInDays($today);

            if ($daysSinceLast > 0) {
                $occurrenceCount = max(1, (int) $occurrence->occurrence_count);
                $totalDays = ((int) ($occurrence->total_days ?? 0)) + $daysSinceLast;
                $newAvgDays = (int) round($totalDays / ($occurrenceCount + 1));

                $occurrence->total_days = $totalDays;
                $occurrence->previous_days_count = $daysSinceLast;
                $occurrence->occurrence_count = $occurrenceCount + 1;
            }
        }

        $occurrence->last_date = $today->format('Y-m-d');
        $occurrence->avg_days_passed = $newAvgDays;
        $occurrence->save();

        return redirect()->back();
    }
}
