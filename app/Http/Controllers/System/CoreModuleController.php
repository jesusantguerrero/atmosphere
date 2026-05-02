<?php

namespace App\Http\Controllers\System;

use App\Domains\AppCore\Data\CoreModuleTypeEnum;
use App\Domains\AppCore\Models\CoreModule;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoreModuleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $allowed = array_map(fn ($case) => $case->name, CoreModuleTypeEnum::cases());

        $data = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*.name' => ['required', 'string', Rule::in($allowed)],
            'modules.*.enabled' => ['required', 'boolean'],
        ]);

        $teamId = $request->user()->current_team_id;

        foreach ($data['modules'] as $module) {
            CoreModule::where([
                'team_id' => $teamId,
                'name' => $module['name'],
            ])->update(['enabled' => $module['enabled']]);
        }

        return back();
    }
}
