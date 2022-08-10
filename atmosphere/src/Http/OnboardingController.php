<?php

namespace Freesgen\Atmosphere\Http;

use App\Actions\Jetstream\CreateTeam;
use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use Freesgen\Atmosphere\Http\Controllers\SettingsController;
use Freesgen\Atmosphere\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index() {
        return Inertia::render('Onboarding/CreateTeam', [
            'invitations' => TeamInvitation::where('email', auth()->user()->email)->with(['team', 'team.owner'])->get(),
        ]);
    }

    public function store(Request $request, CreateTeam $createTeam) {
        $postData = $request->post();
        $entryData = [
            'user_id' =>  $request->user()->id,
            'team_id' => $request->user()->current_team_id
        ];
        Setting::storeBulk($postData, $entryData);
        $createTeam->createUserTeam($request->user(), $postData['team_name']);

        return Redirect('/dashboard');
    }
}
