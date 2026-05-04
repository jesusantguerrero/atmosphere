<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use Modules\Watchlist\Models\Watchlist;
use PHPUnit\Framework\TestCase;

class WatchlistProjectionTest extends TestCase
{
    public function test_projects_linear_when_inside_period(): void
    {
        // Period: full Jan 2026 (31 days). "Now" = Jan 10 → 10 days elapsed.
        // Total spent: $100 → projection = 100 * (31 / 10) = $310
        $now = Carbon::create(2026, 1, 10, 12, 0, 0);

        $result = Watchlist::projectedTotal(100.00, '2026-01-01', '2026-01-31', $now);

        $this->assertSame(310.00, $result['projected']);
        $this->assertSame(10, $result['days_elapsed']);
        $this->assertSame(31, $result['days_in_period']);
        $this->assertTrue($result['is_current_period']);
    }

    public function test_no_projection_when_period_is_in_the_past(): void
    {
        // Looking back at March from May → projected = total, no extrapolation
        $now = Carbon::create(2026, 5, 15, 12, 0, 0);

        $result = Watchlist::projectedTotal(450.00, '2026-03-01', '2026-03-31', $now);

        $this->assertSame(450.00, $result['projected']);
        $this->assertNull($result['days_elapsed']);
        $this->assertNull($result['days_in_period']);
        $this->assertFalse($result['is_current_period']);
    }

    public function test_no_projection_when_period_is_in_the_future(): void
    {
        // Looking forward at June from May → projected = total, no extrapolation
        $now = Carbon::create(2026, 5, 15, 12, 0, 0);

        $result = Watchlist::projectedTotal(0.00, '2026-06-01', '2026-06-30', $now);

        $this->assertSame(0.00, $result['projected']);
        $this->assertFalse($result['is_current_period']);
    }

    public function test_projection_on_first_day_of_period(): void
    {
        // Day 1: 1 day elapsed, 31 in period. $50 spent → projection $1,550.
        $now = Carbon::create(2026, 1, 1, 12, 0, 0);

        $result = Watchlist::projectedTotal(50.00, '2026-01-01', '2026-01-31', $now);

        $this->assertSame(1550.00, $result['projected']);
        $this->assertSame(1, $result['days_elapsed']);
        $this->assertSame(31, $result['days_in_period']);
    }

    public function test_projection_on_last_day_of_period_equals_total(): void
    {
        // Day 31 of 31: projection = total (no extrapolation needed)
        $now = Carbon::create(2026, 1, 31, 12, 0, 0);

        $result = Watchlist::projectedTotal(900.00, '2026-01-01', '2026-01-31', $now);

        $this->assertSame(900.00, $result['projected']);
        $this->assertSame(31, $result['days_elapsed']);
        $this->assertSame(31, $result['days_in_period']);
    }

    public function test_projection_with_zero_total_remains_zero(): void
    {
        // No spending yet → projection stays 0 regardless of how many days elapsed
        $now = Carbon::create(2026, 1, 15, 12, 0, 0);

        $result = Watchlist::projectedTotal(0.00, '2026-01-01', '2026-01-31', $now);

        $this->assertSame(0.00, $result['projected']);
        $this->assertTrue($result['is_current_period']);
    }

    public function test_projection_handles_february_28_days(): void
    {
        // Feb 2026 (28 days, non-leap). Day 14, $200 spent → projection $400.
        $now = Carbon::create(2026, 2, 14, 12, 0, 0);

        $result = Watchlist::projectedTotal(200.00, '2026-02-01', '2026-02-28', $now);

        $this->assertSame(400.00, $result['projected']);
        $this->assertSame(14, $result['days_elapsed']);
        $this->assertSame(28, $result['days_in_period']);
    }
}
