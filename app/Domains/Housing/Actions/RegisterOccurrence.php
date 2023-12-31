<?php

namespace App\Domains\Housing\Actions;

use Exception;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Housing\Data\OccurrenceData;
use App\Domains\Transaction\Actions\SearchTransactions;

class RegisterOccurrence
{
    private function softAdd(Occurrence $occurrence, $date) {
        $lastDuration = $occurrence->last_date ? $this->getDaysDifference($occurrence->last_date->format('Y-m-d'), $date) : 0;
        $log = (array) $occurrence->log ?? [];
        $log[] = $date;
        $occurrenceCount = count($log);
        $totalDays = $occurrenceCount > 1 ? $this->getDaysDifference($log[0], $date) : $lastDuration;

        $occurrence->last_date = $date;
        $occurrence->previous_days_count = $lastDuration;
        $occurrence->occurrence_count = $occurrenceCount;
        $occurrence->log = $log;
        $occurrence->total_days = $totalDays;
        $occurrence->avg_days_passed = $totalDays / $occurrenceCount;
    }

    public function add(int $teamId, string $name, string $date)
    {
        $occurrence = Occurrence::byTeam($teamId)->byName($name)->first();
        if ($occurrence && $occurrence->last_date !== $date) {
            $this->softAdd($occurrence, $date);
            $occurrence->save();
            return;
        }

        Occurrence::create([
                'name' => $name,
                'team_id' => $teamId,
                'last_date' => $date,
                'previous_days_count' => 0,
                'total_days' => 0,
                'avg_days_passed' => 0,
                'log' => [],
        ]);
    }

    public function remove(int $id, string $date = null)
    {
        $occurrence = Occurrence::find($id);

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

    public function load(Occurrence $occurrence)
    {
        $transactions = (new SearchTransactions())->handle($occurrence->conditions);
        foreach ($transactions as $transaction) {
            try {
                $this->softAdd($occurrence, $transaction->date);
            } catch (Exception) {
                continue;
            }
        }
        $occurrence->update();
    }

    public function sync(Occurrence $occurrence)
    {

        $transactions = (new SearchTransactions())->handle($occurrence->conditions);
        $occurrence->log = [];
        $occurrence->saveQuietly();


        $dates = $transactions->pluck('date')->toArray();


        foreach ($dates as $date) {
            try {
                $this->softAdd(
                    $occurrence,
                    $date
                );
                $occurrence->save();
            } catch (\Exception $e) {
                throw $e;
                Log::error($e->getMessage());
                continue;
            }
        }
    }

    private function getDaysDifference($startDate, $endDate, $format = 'Y-m-d')
    {
        return Carbon::createFromFormat($format, $endDate)->diffInDays(Carbon::createFromFormat($format, $startDate));
    }

    public function fromImport(User $user, OccurrenceData $data) {
        Occurrence::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            ...$data->toArray(),
            "name" => $data->name . " copy"
        ]);
    }
}
