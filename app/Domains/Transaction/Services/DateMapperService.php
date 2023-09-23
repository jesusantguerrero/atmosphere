<?php

namespace App\Domains\Transaction\Services;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Carbon;

class DateMapperService
{
    public static function mapInMonths($data, $year)
    {
        $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

        return array_map(function ($month) use ($data, $year) {
            $index = array_search($month, array_column($data, 'months'));

            return $index !== false ? $data[$index] : [
                'year' => $year,
                'months' => $month,
                'total' => 0,
            ];
        }, $months);
    }

    public static function mapInDays($data, $month, $year)
    {
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $dataByDate = [];
        $lastAmount = 0;
        for ($day = 1; $day <= $days; $day++) {
            $padDay = str_pad($day, 2, '0', STR_PAD_LEFT);
            $index = "$year-$month-$padDay";

            $lastAmount = isset($data[$index]) ?
            Money::of($data[$index][0]['total'] ?? $data[$index][0]['total_amount'], 'USD', null, RoundingMode::HALF_EVEN)->plus(Money::of($lastAmount, 'USD', null, RoundingMode::HALF_EVEN))->getAmount() : $lastAmount;
            $dataByDate[] = [
                'month' => $month,
                'days' => $day,
                'total' => Carbon::createFromFormat('Y-m-d', $index)->isBefore(now()) ? $lastAmount : null,
            ];
        }

        return $dataByDate;
    }
}
