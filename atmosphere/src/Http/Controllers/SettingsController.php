<?php

namespace Freesgen\Atmosphere\Http\Controllers;

use App\Actions\Journal\CreateTeamSettings;
use Illuminate\Http\Request;
use App\Models\Setting;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Response;
use Inertia\Inertia;

class SettingsController extends InertiaController
{
    protected $teamNickname = "team";
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        return Inertia::render('Settings/Index', $this->getIndexProps($request));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function section(Request $request, $sectionName)
    {
        $name = $sectionName ?? $this->teamNickname;
        $businessData = [];
        $teamId = $request->user()->current_team_id;
        if ($name !== $this->teamNickname) {
            $businessData = Setting::getByTeam($teamId);
        }

        return Inertia::render("Settings/".ucfirst($name), array_merge([
            "settingData" => Setting::getBySection($teamId, $name),
            "{$this->teamNickname}Data" => $businessData
        ], []));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Response $response)
    {
        $settings = $request->post();
        $entryData = [
            'user_id' =>  $request->user()->id,
            'team_id' => $request->user()->current_team_id
        ];

        $res = Setting::storeBulk($settings, $entryData);

        if ($this->responseType == 'inertia') {
            return redirect()->back();
        } else {
            return $response->setContent($res);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, string|int $id)
    {
       return $this->section($request, $id);
    }
}
