<?php

namespace App\Http\Controllers\System;

use App\Actions\Jetstream\CreateTeam;
use Freesgen\Atmosphere\Http\OnboardingController as BaseOnboardingController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OnboardingController extends BaseOnboardingController
{
    public function store(Request $request, CreateTeam $createTeam)
    {
        $request->validate([
            'language' => ['nullable', 'string', Rule::in(['en', 'es'])],
        ]);

        if ($language = $request->input('language')) {
            $request->user()->forceFill(['language' => $language])->save();
        }

        return parent::store($request, $createTeam);
    }
}
