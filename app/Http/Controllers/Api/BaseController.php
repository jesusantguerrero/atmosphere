<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Freesgen\Atmosphere\Http\Querify;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

abstract class BaseController extends Controller
{
    protected $model;

    protected $createdMessage = 'created';

    protected $searchable = ['id'];

    protected $validationRules = [];

    protected $sorts = [];

    protected $includes = [];

    protected $appends = [];

    protected $filters = [];

    use Querify;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $queryParams = $request->query() ?? [];
        $queryParams['limit'] = $queryParams['limit'] ?? 50;
        $results = $this->getModelQuery($request);

        return $this->parser($results);
    }

    protected function parser($results)
    {
        return $results;
    }

    protected function getFilterDates($filters = [], $subCount = 0)
    {
        $dates = isset($filters['date']) ? explode('~', $filters['date']) : [
            Carbon::now()->subMonths($subCount)->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d'),
        ];

        return $dates;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->validateLocal($request);
        $data = $request->post();
        $data['user_id'] = $request->user()->id;
        $data['team_id'] = $request->user()->current_team_id;
        $resource = $this->model::create($data);

        return [
            'message' => $this->createdMessage,
            'data' => $resource,
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id, Request $request)
    {
        $queryParams = $request->query();
        $relationships = isset($queryParams['relationships']) ? $queryParams['relationships'] : [];

        return $this->teamQuery($request)->with($relationships)->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $resource = $this->teamQuery($request)->findOrFail($id);
        $resource->update(Arr::except($request->post(), ['team_id', 'user_id']));

        return $resource;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $resource = $this->teamQuery(request())->findOrFail($id);
        $resource->delete();

        return $resource;
    }

    /**
     * Records of the current team only; every by-id action goes through this.
     */
    protected function teamQuery(Request $request): Builder
    {
        return $this->model::query()
            ->when($this->authorizedTeam, fn (Builder $query) => $query->where('team_id', $request->user()->current_team_id));
    }

    public function validateLocal(Request $request)
    {
        return $request->validate($this->validationRules);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function bulkDelete(Request $request)
    {
        $items = $request->post();
        $this->teamQuery($request)->whereIn('id', $items)->delete();

        return $items;
    }
}
