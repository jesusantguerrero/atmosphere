<?php

namespace Tests\Feature\LogerProfile;

use Tests\TestCase;

class ProfileWorkspacePreviewRouteTest extends TestCase
{
    public function test_workspace_requires_authentication(): void
    {
        $this->get('/loger-profiles/1')->assertRedirect(route('login'));
        $this->postJson('/loger-profiles/1/workspace/boards', ['name' => 'Example', 'view' => 'list'])->assertUnauthorized();
    }
}
