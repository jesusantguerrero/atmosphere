<?php

namespace Freesgen\Atmosphere\Http;

use App\Actions\Jetstream\CreateTeam;
use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use DateTimeZone;
use ErnySans\Laraworld\Facades\Countries;
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
            'currencies' => collect(Countries::all())->map(function ($country) {
                    return [
                        'name' => $country->name,
                        'code' => $country->currency_code,
                        'symbol' => $country->currency
                    ];
                }
            )->filter(function ($curr) {
                return isset($curr['code']);
            })->unique('code')->sortBy('code')->values()->all(),
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    public function store(Request $request, CreateTeam $createTeam) {
        $postData = $request->post();
        $team = $createTeam->createUserTeam($request->user(), $postData['team_name']);
        $entryData = [
            'user_id' =>  $request->user()->id,
            'team_id' => $team->id
        ];
        Setting::storeBulk($postData, $entryData);

        return Redirect('/dashboard');
    }
}
