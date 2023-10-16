<?php

namespace App\Domains\Housing\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OccurrenceExport implements FromArray, WithHeadings
{
    protected $occurrences;

    public function __construct(array $occurrences)
    {
        $this->occurrences = $occurrences;
    }

    public function array(): array
    {
       return collect($this->occurrences)->map(function ($check) {
            // if (count($check['log']) > 1) {
            //     dd($check["name"], $check['log']);
            // }

            return [
                $check['name'],
                $check['last_date'],
                $check['previous_days_count'],
                $check['avg_days_passed'],
                $check['notify_on_avg'],
                $check['notify_on_last_count'],
                $check['conditions'],
                $check['type'],
                $check['log'],
                $check['is_active'],
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Last occurrence at',
            'Previous duration(days)',
            'Frequency (avg in days)',
            'Notify on avg?',
            'Notify on previous duration?',
            'Conditions',
            'Type',
            'Logs',
            'Is active',
        ];
    }
}
