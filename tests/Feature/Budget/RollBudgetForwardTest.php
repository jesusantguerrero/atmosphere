<?php

namespace Tests\Feature\Budget;

use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Services\BudgetRolloverService;
use App\Events\BudgetAssigned;
use App\Jobs\RollBudgetForward;
use App\Listeners\CreateBudgetMovement;
use App\Listeners\UpdateBudgetAvailable;
use Illuminate\Support\Facades\Queue;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Models\Core\Transaction;
use Tests\TestCase;

/**
 * Transaction and budget-assignment events used to run the full rollover
 * inline in their listeners, so a bank import of N transactions triggered N
 * rollovers. They now dispatch a job that is unique per team + month while
 * it waits in the queue.
 */
class RollBudgetForwardTest extends TestCase
{
    public function test_duplicate_dispatches_for_the_same_team_and_month_collapse_into_one_job(): void
    {
        Queue::fake();

        RollBudgetForward::dispatch(1, '2026-08');
        RollBudgetForward::dispatch(1, '2026-08');
        RollBudgetForward::dispatch(1, '2026-08');

        Queue::assertPushed(RollBudgetForward::class, 1);
    }

    public function test_different_months_or_teams_queue_separate_jobs(): void
    {
        Queue::fake();

        RollBudgetForward::dispatch(1, '2026-08');
        RollBudgetForward::dispatch(1, '2026-09');
        RollBudgetForward::dispatch(2, '2026-08');

        Queue::assertPushed(RollBudgetForward::class, 3);
    }

    public function test_job_rolls_the_team_forward_from_the_given_month(): void
    {
        $service = $this->mock(BudgetRolloverService::class);
        $service->shouldReceive('startFrom')->once()->with(7, '2026-08');

        (new RollBudgetForward(7, '2026-08'))->handle($service);
    }

    public function test_verified_transaction_event_dispatches_the_rollover_for_its_month(): void
    {
        Queue::fake();

        (new UpdateBudgetAvailable)->handle(new TransactionCreated($this->transaction(Transaction::STATUS_VERIFIED)));

        Queue::assertPushed(RollBudgetForward::class, fn (RollBudgetForward $job) => $job->uniqueId() === '7:2026-08');
    }

    public function test_draft_transaction_event_does_not_dispatch_the_rollover(): void
    {
        Queue::fake();

        (new UpdateBudgetAvailable)->handle(new TransactionCreated($this->transaction(Transaction::STATUS_DRAFT)));

        Queue::assertNotPushed(RollBudgetForward::class);
    }

    public function test_budget_assignment_dispatches_the_rollover_for_its_month(): void
    {
        Queue::fake();

        $assignment = new BudgetAssignData(team_id: 7, user_id: 1, date: '2026-08-01', category_id: 3, amount: 100);
        (new CreateBudgetMovement)->handle(new BudgetAssigned($assignment));

        Queue::assertPushed(RollBudgetForward::class, fn (RollBudgetForward $job) => $job->uniqueId() === '7:2026-08');
    }

    private function transaction(string $status): Transaction
    {
        return new Transaction([
            'team_id' => 7,
            'date' => '2026-08-15',
            'status' => $status,
        ]);
    }
}
