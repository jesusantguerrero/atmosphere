<?php

namespace App\Http\Controllers;

use App\Domains\Transaction\Models\Watchlist;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;
use Freesgen\Atmosphere\Http\InertiaController;
use Freesgen\Atmosphere\Http\Querify;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class FinanceWatchlistController extends InertiaController {
    use Querify;
    const DateFormat = 'Y-m-d';


    public function __construct(Watchlist $watchlist)
    {
        $this->model = $watchlist;
        $this->templates = [
            'index' => 'Finance/Watchlist'
        ];
        $this->searchable = ["id", "name"];
        $this->sorts = ['name'];
        $this->includes = [];
        $this->appends = [];
    }

    public function getIndexProps(Request $request, Collection|ResourceCollection $watchlist): array {
        $teamId = $request->user()->current_team_id;
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        return [
            "data" => array_map(function($item) use ($teamId, $startDate, $endDate) {
                return array_merge($item, [
                    "data" => Watchlist::getData($teamId, $item, $startDate, $endDate)
                ]);
            }, $watchlist->toArray())
        ];
    }
}
