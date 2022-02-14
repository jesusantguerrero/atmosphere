<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Board;
use App\Models\Setting;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $tabName = "business")
    {
        return Inertia::render('Settings/Index', [
            "tabName" =>  $tabName,
            "settingData" => Setting::getBySection($request->user()->current_team_id, $tabName)
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function section(Request $request, $name = "business")
    {
        $businessData = [];
        if ($name !== 'business') {
            $businessData = Setting::getByTeam($request->user()->current_team_id);
        }
        return Inertia::render("Settings/".ucfirst($name), [
            "settingData" => Setting::getBySection($request->user()->current_team_id, $name),
            "businessData" => $businessData
        ]);
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

        foreach ($settings as $settingName => $setting) {

            $setting = array_merge($entryData, [
                "value" => $setting,
                "name" => $settingName
            ]);
            $resource = Setting::where([
                'user_id' =>  $request->user()->id,
                'team_id' => $request->user()->current_team_id,
                'name' => $settingName
            ])->limit(1)->get();

            if (count($resource)) {
                $resource[0]->update($setting);
            } else {
                $resource = Setting::create($setting);
            }
        }

        $res = Setting::getFormatted([
            'user_id' =>  $request->user()->id,
            'team_id' => $request->user()->current_team_id
        ]);

        return $response->setContent($res);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, string $id)
    {
        $businessData = [];
        if ($id !== 'business') {
            $businessData = Setting::getByTeam($request->user()->current_team_id);
        }

        return Inertia::render("Settings/".ucfirst($id), [
            "settingData" => Setting::getBySection($request->user()->current_team_id, $id),
            "businessData" => $businessData
        ]);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $board = Board::find($id);
        $board->update($request->post());
        return $board;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $board = Board::find($id);
        $board->deleteStages();
        $board->delete();
    }
}
