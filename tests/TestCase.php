<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Test-only RSA pair so Passport (MCP OAuth) can sign and verify tokens.
        Passport::loadKeysFrom(base_path('tests/Fixtures/oauth'));
        Passport::$validateKeyPermissions = false;
    }
}
