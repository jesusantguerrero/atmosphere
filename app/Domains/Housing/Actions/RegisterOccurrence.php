<?php

namespace App\Domains\Housing\Actions;

use App\Domains\Housing\Contracts\OccurrenceNotifyTypes;
use App\Domains\Housing\Data\OccurrenceData;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Transaction\Actions\SearchTransactions;
use App\Models\User;
use App\Notifications\OccurrenceAlert;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RegisterOccurrence
{
    private function softAdd(Occurrence $occurrence, $date)
    {
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
        if ($occurrence && ($occurrence->last_date && $occurrence->last_date->format('Y-m-d') !== $date || ! $occurrence->last_date)) {
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

    public function remove(int $id, ?string $date = null)
    {
        $occurrence = Occurrence::find($id);

        if ($occurrence && $occurrence->last_date) {
            $log = $occurrence->log;
            array_pop($log);
            $previousLastDate = count($log) > 1 ? $log[count($log) - 2] : null;
            $lastDate = $log[count($log) - 1];

            $lastDuration = $previousLastDate ? $this->getDaysDifference($lastDate, $previousLastDate) : 0;
            $occurrenceCount = count($occurrence->log);
            $totalDays = $occurrenceCount > 1 ? $this->getDaysDifference($log[0], $lastDate) : $lastDuration;
            $avg = $totalDays / $occurrenceCount;

            $occurrence->update([
                'last_date' => $lastDate,
                'previous_days_count' => $lastDuration,
                'total_days' => $totalDays,
                'avg_days_passed' => $avg,
                'occurrence_count' => $occurrenceCount,
                'log' => $log,
            ]);
        }
    }

    public function load(Occurrence $occurrence): void
    {
        $this->sync($occurrence);
    }

    public function sync(Occurrence $occurrence): void
    {
        $transactions = app(SearchTransactions::class)->handle($occurrence->conditions, $occurrence->team_id);
        if ($transactions === null || $transactions->isEmpty()) {
            return;
        }

        $dates = $transactions->pluck('date')
            ->map(fn ($date) => $this->normalizeDate($date))
            ->filter()->unique()->sort()->values()->all();
        if (! $dates) {
            return;
        }

        $count = count($dates);
        $lastDate = $count ? $dates[$count - 1] : null;
        $totalDays = $count > 1 ? $this->getDaysDifference($dates[0], $lastDate) : 0;
        $previousDays = $count > 1 ? $this->getDaysDifference($dates[$count - 2], $lastDate) : 0;

        $occurrence->update([
            'log' => $dates,
            'last_date' => $lastDate,
            'occurrence_count' => $count,
            'total_days' => $totalDays,
            'previous_days_count' => $previousDays,
            'avg_days_passed' => $count > 1 ? $totalDays / ($count - 1) : 0,
        ]);
    }

    private function normalizeDate(mixed $date): ?string
    {
        if ($date === null || $date === '' || (! is_string($date) && ! $date instanceof \DateTimeInterface)) {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (InvalidFormatException) {
            Log::warning('Occurrence sync skipped an invalid transaction date.');

            return null;
        }
    }

    public function remind(Occurrence $occurrence)
    {
        User::find($occurrence->user_id)->notify(new OccurrenceAlert($occurrence, OccurrenceNotifyTypes::LAST));
        User::find($occurrence->user_id)->notify(new OccurrenceAlert($occurrence, OccurrenceNotifyTypes::AVG));
    }

    private function getDaysDifference($startDate, $endDate, $format = 'Y-m-d')
    {
        // Robust to date / datetime / Carbon inputs. Transaction dates can come
        // through as 'Y-m-d H:i:s' or Carbon instances, which createFromFormat
        // with a strict 'Y-m-d' would throw on -- and a throw here used to abort
        // the whole occurrence sync.
        return (int) Carbon::parse($endDate)->startOfDay()->diffInDays(Carbon::parse($startDate)->startOfDay(), true);
    }

    public function fromImport(User $user, OccurrenceData $data)
    {
        Occurrence::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            ...$data->toArray(),
            'name' => $data->name.' copy',
        ]);
    }
}
