<?php

namespace Atmosphere\Http;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class InertiaController extends BaseController {
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

    public function store(Request $request) {
        $postData = $request->post();

        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $this->model::create($postData);
        return Redirect::back();
    }

    protected function getIndexProps(Request $request) {
        return [
            $this->model->getTable() => $this->model->all()
        ];
    }
}
