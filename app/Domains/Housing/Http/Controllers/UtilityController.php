<?php

namespace App\Domains\Housing\Http\Controllers;

use App\Domains\Housing\Models\Occurrence;
use App\Http\Controllers\Controller;
use Inertia\Response;

class UtilityController extends Controller
{
    public function __invoke(): Response
    {
        $teamId = request()->user()->current_team_id;

        $utilities = Occurrence::query()
            ->byTeam($teamId)
            ->ofType(Occurrence::TYPE_UTILITY)
            ->orderBy('name')
            ->get()
            ->map(function (Occurrence $occurrence) {
                $daysUntil = $occurrence->daysUntilNext();
                $nextDate = $occurrence->last_date && $occurrence->avg_days_passed
                    ? $occurrence->last_date->copy()->addDays((int) $occurrence->avg_days_passed)->format('Y-m-d')
                    : null;

                return [
                    'id' => $occurrence->id,
                    'name' => $occurrence->name,
                    'last_date' => optional($occurrence->last_date)->format('Y-m-d'),
                    'avg_days_passed' => $occurrence->avg_days_passed,
                    'due_at' => $nextDate,
                    'days_until' => $daysUntil,
                    'is_overdue' => $daysUntil !== null && $daysUntil < 0,
                    'is_active' => (bool) $occurrence->is_active,
                ];
            });

        return inertia('Housing/Utilities', [
            'utilities' => $utilities,
        ]);
    }
}
