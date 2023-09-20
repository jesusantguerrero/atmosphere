<?php

namespace App\Concerns;

trait SupportsDateFrame
{
    public function scopeInDateFrame($query, $startDate = null, $endDate = null, $orderByDate = true, $field = 'date')
    {
        return $query
            ->when($startDate && ! $endDate, function ($query) use ($startDate, $field) {
                $query->where($field, '=', $startDate);
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate, $field) {
                $query->whereBetween($field, [$startDate, $endDate]);
            })
            ->when(! $startDate && $endDate, function ($query) use ($endDate, $field) {
                $query->where($field, '<=', $endDate);
            })
            ->when($orderByDate, function ($query) use ($field) {
                $query->orderBy($field, 'desc');
            });
    }

    public function scopeByTeamId($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }
}
