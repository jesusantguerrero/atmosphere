<?php

namespace Tests\Feature;

use Tests\TestCase;

class RouteNamesTest extends TestCase
{
    public function test_relationship_route_names_do_not_collide(): void
    {
        $this->assertSame('/relationships', route('relationships.index', [], false));
        $this->assertSame(
            '/relationships/partner',
            route('relationships.profile', ['profileName' => 'partner'], false)
        );
    }

    public function test_routes_can_be_cached_without_duplicate_name_errors(): void
    {
        $exitCode = $this->artisan('route:cache');

        $exitCode->assertSuccessful();

        $this->artisan('route:clear');
    }
}
