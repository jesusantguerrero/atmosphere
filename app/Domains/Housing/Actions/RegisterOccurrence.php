<?php

namespace App\Domains\Housing\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Domains\Housing\Models\OccurrenceCheck;
use App\Domains\Transaction\Actions\SearchTransactions;

class RegisterOccurrence
{
    public function add(int $teamId, string $name, string $date)
    {
        $occurrence = OccurrenceCheck::where([
            'team_id' => $teamId,
            'name' => $name,
        ])->first();

        if ($occurrence && $occurrence->last_date !== $date) {
            $lastDuration = $occurrence->last_date ? $this->getDaysDifference($occurrence->last_date, $date) : 0;
            $totalDays = $occurrence->total_days + $lastDuration;
            $occurrenceCount = $occurrence->occurrence_count + 1;
            $avg = $totalDays / $occurrenceCount;
            $log = (array) $occurrence->log ?? [];
            $log[] = $date;

            $occurrence->update([
                'last_date' => $date,
                'previous_days_count' => $lastDuration,
                'total_days' => $totalDays,
                'avg_days_passed' => $avg,
                'occurrence_count' => $occurrenceCount,
                'log' => $log,
            ]);
        } elseif (! $occurrence) {
            OccurrenceCheck::create([
                'name' => $name,
                'team_id' => $teamId,
                'last_date' => $date,
                'previous_days_count' => 0,
                'total_days' => 0,
                'avg_days_passed' => 0,
                'log' => [],
            ]);

        }
    }

    public function remove(int $id, string $date = null)
    {
        $occurrence = OccurrenceCheck::find($id);

        if ($occurrence && $occurrence->last_date) {
            $log = $occurrence->log;
            array_pop($log);
            $previousLastDate = count($log) > 1 ? $log[count($log) - 2] : null;
            $lastDate = $log[count($log) - 1];

            $lastDuration = $previousLastDate ? $this->getDaysDifference($lastDate, $previousLastDate) : 0;
            $totalDays = $occurrence->total_days - $occurrence->previous_days_count;
            $occurrenceCount = $occurrence->occurrence_count - 1;
            $avg = $totalDays / $occurrenceCount;

            $occurrence->update([
                'last_date' => $lastDate,
                'previous_days_count' => $lastDuration,
                'total_days' => $totalDays,
                'avg_days_passed' => $avg,
                'occurrence_count' => $occurrenceCount,
                'log' => json_encode($log),
            ]);
        }
    }

    public function load(OccurrenceCheck $occurrence)
    {
        $transactions = (new SearchTransactions())->handle($occurrence->conditions);
        foreach ($transactions as $transaction) {
            try {
                (new RegisterOccurrence())->add(
                    $transaction->team_id,
                    $occurrence->name,
                    $transaction->date
                );
            } catch (\Exception $e) {
                Log::error($e->getMessage());

                continue;
            }
        }
    }

    public function sync(OccurrenceCheck $occurrence)
    {
        $transactions = (new SearchTransactions())->handle($occurrence->conditions);

        foreach ($transactions as $transaction) {
            try {
                (new RegisterOccurrence())->add(
                    $transaction->team_id,
                    $occurrence->name,
                    $transaction->date
                );
            } catch (\Exception $e) {
                Log::error($e->getMessage());

                continue;
            }
        }
    }

    private function getDaysDifference($startDate, $endDate, $format = 'Y-m-d')
    {
        return Carbon::createFromFormat($format, $endDate)->diffInDays(Carbon::createFromFormat($format, $startDate));
    }
}
