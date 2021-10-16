<?php

namespace Atmosphere\Http;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class InertiaController extends BaseController {
    use Querify;
    protected $model;
    protected $templates;
    protected $searchable = ["id"];
    protected $validationRules = [];
    protected $sorts = [];
    protected $includes = [];
    protected $appends = [];
    protected $filters = [];

    public function index(Request $request) {
        return Inertia::render($this->templates['index'], $this->getIndexProps($request));
    }

    public function create(Request $request) {
        return Inertia::render($this->templates['create'], []);
    }

    public function edit(Request $request, int $id) {
        return Inertia::render($this->templates['edit'], $this->getEditProps($request, $id));
    }

    public function store(Request $request) {
        $postData = $request->post();
        $this->validate($request, $this->validationRules);
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $resource = $this->model::create($postData);
        $this->afterSave($postData, $resource);
        return Redirect::back();
    }

    public function update(Request $request, int $id) {
        $resource = $this->model::find($id);
        $postData = $request->post();
        $resource->update($postData);
        $this->afterSave($postData, $resource);
        return Redirect::back();
    }

    public function destroy(Request $request, int $id) {
        $resource = $this->model::find($id);
        $resource->delete();
        return Redirect::back();
    }

    protected function getIndexProps(Request $request) {
        $queryParams = $request->query() ?? [];
        $queryParams['limit'] = $queryParams['limit'] ?? 50;

        return [
            $this->model->getTable() => $this->getModelQuery($request)
        ];
    }

    protected function getEditProps(Request $request, $id) {
        return [
            $this->model->getTable() => $this->getModelQuery($request, $id)[0]
        ];
    }

    protected function afterSave($postData, $resource): void {

    }

    protected function getPostData(Request $request) {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;

        return $postData;
    }
}
