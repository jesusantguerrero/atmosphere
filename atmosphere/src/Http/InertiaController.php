<?php

namespace Freesgen\Atmosphere\Http;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
    protected $responseType = "inertia";

    public function index(Request $request) {
        return Inertia::render($this->templates['index'], $this->getIndexProps($request));
    }

    public function create(Request $request) {
        return Inertia::render($this->templates['create'], []);
    }

    public function edit(Request $request, int $id) {
        return Inertia::render($this->templates['edit'], $this->getEditProps($request, $id));
    }

    public function store(Request $request, Response $response) {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $this->validate($request, $this->getValidationRules($postData));
        $resource = $this->model::create($postData);
        $this->afterSave($postData, $resource);
        if ($this->responseType == 'inertia') {
            return redirect()->back();
        } else {
            return $response->setContent($resource);
        }
    }

    public function update(Request $request, int $id) {
        $resource = $this->model::find($id);
        $postData = $request->post();
        $resource->update($postData);
        $this->afterSave($postData, $resource);
        if ($this->responseType == 'inertia') {
            return Redirect::back();
        } else {
            return $resource;
        }
    }

    public function destroy(Request $request, int $id) {
        $resource = $this->model::find($id);
        if ($this->validateDelete($request, $resource)) {
            $resource->delete();
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors(['error' => 'You cannot delete this resource']);
        }
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

    protected function validateDelete(Request $request, $resource) {
        return true;
    }

    protected function getValidationRules($postData) {
        return $this->validationRules;
    }

}
