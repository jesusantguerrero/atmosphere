<?php

namespace Tests\Feature\System;

use App\Domains\AppCore\Data\CoreModuleTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreModulesDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_finance_module_is_enabled_for_a_new_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $modules = $team->modules->keyBy(fn ($module) => strtolower($module->name));

        $this->assertTrue((bool) $modules['finance']->enabled);
        $this->assertFalse((bool) $modules['meals']->enabled);
        $this->assertFalse((bool) $modules['housing']->enabled);
        $this->assertFalse((bool) $modules['profiles']->enabled);
    }

    public function test_user_can_toggle_modules(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $response = $this->patch('/user/modules', [
            'modules' => [
                ['name' => CoreModuleTypeEnum::Meals->name, 'enabled' => true],
                ['name' => CoreModuleTypeEnum::Finance->name, 'enabled' => false],
            ],
        ]);

        $response->assertRedirect();

        $modules = $user->fresh()->ownedTeams()->first()->modules->keyBy(fn ($module) => strtolower($module->name));
        $this->assertTrue((bool) $modules['meals']->enabled);
        $this->assertFalse((bool) $modules['finance']->enabled);
    }

    public function test_module_toggle_rejects_unknown_module_names(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $response = $this->patch('/user/modules', [
            'modules' => [
                ['name' => 'unknown', 'enabled' => true],
            ],
        ]);

        $response->assertSessionHasErrors('modules.0.name');
    }
}
