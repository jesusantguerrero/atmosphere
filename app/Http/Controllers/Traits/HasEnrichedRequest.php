<?php

namespace App\Http\Controllers\Traits;

use App\Models\Setting;
use Illuminate\Support\Carbon;

trait HasEnrichedRequest
{
    protected $model;

    protected $templates;

    protected $searchable = ['id'];

    protected $validationRules = [];

    protected $sorts = [];

    protected $includes = [];

    protected $appends = [];

    protected $filters = [];

    protected $page;

    protected $limit;

    protected $responseType = 'inertia';

    protected $resourceName;

    protected function getPostData()
    {
        $postData = request()->post();
        $postData['user_id'] = request()->user()->id;
        $postData['team_id'] = request()->user()->current_team_id;

        return $postData;
    }

    protected function getFilterDates($filters = [], $subCount = 0)
    {
        $settings = Setting::getByTeam(auth()->user()->current_team_id);
        $timeZone = $settings['team_timezone'] ?? config('app.timezone');

        $dates = isset($filters['date']) ? explode('~', $filters['date']) : [
            Carbon::now()->setTimezone($timeZone)->subMonths($subCount)->startOfMonth()->format('Y-m-d'),
            Carbon::now()->setTimezone($timeZone)->endOfMonth()->format('Y-m-d'),
        ];

        return $dates;
    }

    protected function getServerParams()
    {
        $queryParams = request()->query();
        $limit = $queryParams['limit'] ?? $this->limit;
        $page = $queryParams['page'] ?? $this->page;
        $search = $queryParams['search'] ?? null;
        $sorts = $queryParams['sort'] ?? null;
        $relationships = $queryParams['relationships'] ?? null;
        $filters = $queryParams['filter'] ?? [];

        $this->serverParams = [
            'filters' => $filters,
            'limit' => $limit,
            'search' => $search,
            'sorts' => $sorts,
            'page' => $page,
            'relationships' => $relationships,
        ];
    }
}
