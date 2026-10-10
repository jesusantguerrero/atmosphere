<?php

namespace Tests\Feature\Finance;

use App\Domains\Transaction\Services\CreditCardReportService;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Transaction\Services\TransactionService;
use App\Http\Controllers\Finance\FinanceTrendController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Insane\Journal\Models\Core\Transaction;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class InsightsPeriodTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    public static function periods(): array
    {
        return [
            'YTD' => [10, '2026-01-01'],
            'one month' => [1, '2026-10-01'],
            'three months' => [3, '2026-08-01'],
            'six months' => [6, '2026-05-01'],
            'one year' => [12, '2025-11-01'],
            'invalid range' => [99, '2026-05-01'],
            'YTD from an outdated client' => [1, '2026-01-01', 'YTD'],
        ];
    }

    #[DataProvider('periods')]
    public function test_payee_breakdowns_share_the_selected_chart_period(int $requestedMonths, string $startDate, ?string $range = null): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');
        $months = $range === 'YTD' ? 10 : ($requestedMonths === 99 ? 6 : $requestedMonths);
        $endDate = '2026-10-31';
        $transactions = Mockery::mock('alias:'.TransactionService::class);
        $reports = Mockery::mock('alias:'.ReportService::class);
        $cards = Mockery::mock(CreditCardReportService::class);
        $transactions->shouldReceive('getCategoryExpensesGroup')->once()->with(2, $startDate, $endDate, null, null)->andReturn([]);
        $transactions->shouldReceive('getIncomeVsExpenses')->once()->with(2, $months - 1, 'month', 'expenses', Mockery::type(Carbon::class))->andReturn([
            'expenses' => [['name' => 'School', $startDate => 1100, 'total' => 1100]],
            'incomes' => [['name' => 'Employer', $startDate => 3000, 'total' => 3000]],
        ]);
        $reports->shouldReceive('getLatestExpenseDate')->once()->with(2)->andReturn($endDate);
        $transactions->shouldReceive('getExpensePayeesInPeriod')->once()->with(2, $startDate, $endDate)->andReturn(collect([
            ['name' => 'School', 'total' => 500],
            ['name' => 'School', 'total' => 600],
        ]));
        $transactions->shouldReceive('getTransactionsByPayeeInPeriod')->once()->with(2, $startDate, $endDate, Transaction::DIRECTION_DEBIT)->andReturn(collect([
            ['name' => 'Employer', 'total' => 1000],
            ['name' => 'Employer', 'total' => 2000],
        ]));
        $transactions->shouldReceive('getExpensesByAccountInPeriod')->once()->with(2, $startDate, $endDate)->andReturn(collect());
        $reports->shouldReceive('generateCurrentPreviousReport')->once()->andReturn([]);
        $transactions->shouldReceive('getNetWorth')->once()->with(2, $startDate, $endDate)->andReturn([
            (object) ['date_unit' => '2025-10-31', 'assets' => 100, 'debts' => 0],
            (object) ['date_unit' => $endDate, 'assets' => 200, 'debts' => 0],
        ]);
        $cards->shouldReceive('creditCards')->once()->with(2, $endDate, $startDate, null)->andReturn([]);

        $request = Request::create('/trends', 'GET', ['months' => $requestedMonths, 'range' => $range, 'filter' => ['date' => '2026-10-01~2026-10-31']]);
        $user = new User;
        $user->current_team_id = 2;
        $request->setUserResolver(fn () => $user);
        $result = (new FinanceTrendController($reports, $cards))->insights($request);

        $this->assertSame($months, $result['metaData']['months']);
        $this->assertCount($months, $result['data']['netWorth']);
        $this->assertSame($endDate, $result['data']['netWorth'][0]->date_unit);
        $this->assertSame(200.0, $result['data']['netWorth'][0]->assets);
        $this->assertCount($months, $result['data']['monthlyFlow']);
        $this->assertSame(1100.0, $result['data']['spendingSummary'][$startDate]['total']);
        $this->assertSame(3000.0, $result['data']['monthlyFlow'][0]['income']);
        $this->assertSame(1100.0, $result['data']['payeesOut'][0]['total']);
        $this->assertSame(3000.0, $result['data']['payeesIn'][0]['total']);
    }

    #[DataProvider('historicalPeriods')]
    public function test_period_can_be_navigated_back_and_never_past_the_current_month(int $months, string $range, string $anchor, string $start, string $end): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');
        $transactions = Mockery::mock('alias:'.TransactionService::class);
        $reports = Mockery::mock('alias:'.ReportService::class);
        $cards = Mockery::mock(CreditCardReportService::class);
        $transactions->shouldReceive('getCategoryExpensesGroup')->once()->with(2, $start, $end, null, null)->andReturn([]);
        $transactions->shouldReceive('getIncomeVsExpenses')->once()->with(2, $months - 1, 'month', 'expenses', Mockery::on(fn ($date) => $date->format('Y-m') === $anchor))->andReturn([]);
        $reports->shouldReceive('getLatestExpenseDate')->once()->andReturn('2026-10-31');
        $transactions->shouldReceive('getExpensePayeesInPeriod')->once()->andReturn(collect());
        $transactions->shouldReceive('getTransactionsByPayeeInPeriod')->once()->andReturn(collect());
        $transactions->shouldReceive('getExpensesByAccountInPeriod')->once()->with(2, $start, $end)->andReturn(collect());
        $reports->shouldReceive('generateCurrentPreviousReport')->once()->andReturn([]);
        $transactions->shouldReceive('getNetWorth')->once()->andReturn([]);
        $cards->shouldReceive('creditCards')->once()->with(2, $end, $start, null)->andReturn([]);

        $request = Request::create('/trends', 'GET', ['months' => $months, 'range' => $range, 'end' => $anchor]);
        $user = new User;
        $user->current_team_id = 2;
        $request->setUserResolver(fn () => $user);
        $result = (new FinanceTrendController($reports, $cards))->insights($request);

        $this->assertSame($anchor, $result['metaData']['anchorMonth']);
        $this->assertSame($start, $result['metaData']['startDate']);
        $this->assertSame($end, $result['metaData']['endDate']);
        $this->assertSame($range, $result['metaData']['range']);
        $this->assertCount($months, $result['data']['monthlyFlow']);
        $this->assertFalse($result['metaData']['isCurrentPeriod']);
        $this->assertSame($end, $result['metaData']['asOfDate']);
    }

    public static function historicalPeriods(): array
    {
        return [
            'past month' => [1, '1M', '2026-08', '2026-08-01', '2026-08-31'],
            'card preparation across years' => [6, 'Custom', '2024-01', '2023-08-01', '2024-01-31'],
        ];
    }
}
