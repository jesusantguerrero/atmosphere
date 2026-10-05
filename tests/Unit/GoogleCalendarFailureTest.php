<?php

namespace Tests\Unit;

use App\Domains\Integration\Services\GoogleCalendarService;
use PHPUnit\Framework\TestCase;

class GoogleCalendarFailureTest extends TestCase
{
    public function test_only_insufficient_permissions_requests_scope_reconsent(): void
    {
        $this->assertSame('scope', GoogleCalendarService::failureResult(403, 'insufficientPermissions')['error']);
        $this->assertSame('fetch', GoogleCalendarService::failureResult(403, 'userRateLimitExceeded')['error']);
        $this->assertSame('fetch', GoogleCalendarService::failureResult(403, null)['error']);
        $this->assertSame('fetch', GoogleCalendarService::failureResult(500, null)['error']);
        $this->assertSame('reauth', GoogleCalendarService::failureResult(401, null)['error']);
    }
}
