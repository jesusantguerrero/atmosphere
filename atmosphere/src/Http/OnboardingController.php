<?php

namespace Freesgen\Atmosphere\Http;

use App\Actions\Jetstream\CreateTeam;
use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
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

    public function store(Request $request, Response $response, CreateTeam $createTeam, SettingsController $settings) {
        $postData = $request->post();
        $createTeam->create($request->user(), [
            'name' => $postData['business_name'],
        ], true);

        $settings->store($request, $response);

        return Redirect('/dashboard');
    }
}
