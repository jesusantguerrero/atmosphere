<?php

namespace App\Domains\Housing\Actions;

use App\Domains\Housing\Models\OccurrenceCheck;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Insane\Journal\Contracts\DeleteAccounts;

class RegisterOccurrence
{
    public function add(int $teamId, string $name, string $date)
    {
        $occurrence = OccurrenceCheck::where([
            'team_id' => $teamId,
            'name' => $name
        ])->first();

        if ($occurrence && $occurrence->last_date !== $date) {
            $lastDuration = $this->getDaysDifference($occurrence->last_date, $date);
            $totalDays = $occurrence->total_days + $lastDuration;
            $occurrenceCount = $occurrence->occurrence_count + 1;
            $avg = $totalDays / $occurrenceCount;
            $log = json_decode($occurrence->log);
            $log[] = $date;

            $occurrence->update([
                'last_date' => $date,
                'previous_days_count' => $lastDuration,
                'total_days' => $totalDays,
                'avg_days_passed' => $avg,
                'occurrence_count' => $occurrenceCount,
                'log' => json_encode($log)
            ]);
        } else if (!$occurrence) {
            OccurrenceCheck::create([
                'name' => $name,
                'team_id' => $teamId,
                'last_date' => $date,
                'previous_days_count' => 0,
                'total_days' => 0,
                'avg_days_passed' => 0,
                'log' => json_encode([])
            ]);

        }
    }

    private function getDaysDifference($startDate, $endDate, $format = 'Y-m-d') {
        return Carbon::createFromFormat($format, $endDate)->diffInDays(Carbon::createFromFormat($format, $startDate));
    }
}
