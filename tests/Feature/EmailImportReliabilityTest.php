<?php

namespace Tests\Feature;

use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Integration\Actions\TransactionCreateEntry;
use App\Domains\Transaction\Services\TransactionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailImportReliabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Automation $automation;

    private AutomationTaskAction $trigger;

    private AutomationTaskAction $previousTask;

    private AutomationTaskAction $task;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $team = $this->user->ownedTeams()->first();
        $this->user->forceFill(['current_team_id' => $team->id])->save();
        $teamId = $team->id;

        $this->account = Account::create([
            'team_id' => $teamId,
            'user_id' => $this->user->id,
            'name' => 'BHD Credit Card',
            'currency_code' => 'DOP',
            'display_id' => 'credit_card',
            'account_detail_type_id' => 1,
        ]);

        $this->automation = Automation::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $teamId,
        ]);

        $this->trigger = AutomationTaskAction::factory()->create([
            'automation_id' => $this->automation->id,
            'team_id' => $teamId,
            'user_id' => $this->user->id,
            'name' => 'gmailReceived',
            'entity' => 'App\\Domains\\Integration\\Actions\\GmailReceived',
            'task_type' => 'trigger',
        ]);

        $this->previousTask = AutomationTaskAction::factory()->create([
            'automation_id' => $this->automation->id,
            'team_id' => $teamId,
            'user_id' => $this->user->id,
            'name' => 'BHD Parser',
            'entity' => 'App\\Domains\\Integration\\Actions\\BHDParser',
            'task_type' => 'action',
        ]);

        $this->task = AutomationTaskAction::factory()->create([
            'automation_id' => $this->automation->id,
            'team_id' => $teamId,
            'user_id' => $this->user->id,
            'name' => 'createTransaction',
            'entity' => 'App\\Domains\\Integration\\Actions\\TransactionCreateEntry',
            'task_type' => 'action',
            'values' => json_encode([
                'account_id' => (string) $this->account->id,
                'description' => '${payee} - ${description}',
                'date' => '${date}',
                'direction' => 'WITHDRAW',
                'total' => '${total}',
                'currency_code' => 'DOP',
            ]),
        ]);
    }

    private function makePayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 'gmail_msg_001',
            'messageId' => '<abc@mail.bhd.com>',
            'threadId' => 'thread_001',
            'payee' => 'Supermercado Nacional',
            'description' => 'Compra POS',
            'date' => '2026-10-24',
            'total' => '1500.00',
            'productCode' => null,
            'productBrand' => null,
            'productName' => null,
            'destinationProductName' => null,
            'destinationProductCode' => null,
            'transactionType' => null,
        ], $overrides);
    }

    #[Test]
    public function it_skips_zero_amount_transactions(): void
    {
        $payload = $this->makePayload(['total' => '0']);

        $result = TransactionCreateEntry::handle(
            $this->automation,
            $payload,
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        $this->assertNull($result);
        $this->assertDatabaseCount('transactions', 0);
    }

    #[Test]
    public function it_skips_negative_amount_transactions(): void
    {
        $payload = $this->makePayload(['total' => '-500']);

        $result = TransactionCreateEntry::handle(
            $this->automation,
            $payload,
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        $this->assertNull($result);
        $this->assertDatabaseCount('transactions', 0);
    }

    #[Test]
    public function it_creates_transaction_for_valid_amount(): void
    {
        $payload = $this->makePayload();

        $result = TransactionCreateEntry::handle(
            $this->automation,
            $payload,
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        $this->assertNotNull($result);
        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertEquals(1500.00, (float) $result->total);
    }

    #[Test]
    public function it_records_processed_email_after_creation(): void
    {
        $payload = $this->makePayload();

        TransactionCreateEntry::handle(
            $this->automation,
            $payload,
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        $this->assertDatabaseHas('processed_emails', [
            'team_id' => $this->user->current_team_id,
            'gmail_id' => 'gmail_msg_001',
            'message_id' => '<abc@mail.bhd.com>',
            'automation_id' => $this->automation->id,
        ]);
    }

    #[Test]
    public function it_skips_already_processed_email(): void
    {
        $payload = $this->makePayload();

        $first = TransactionCreateEntry::handle(
            $this->automation,
            $payload,
            $this->task,
            $this->previousTask,
            $this->trigger
        );
        $this->assertNotNull($first);

        $second = TransactionCreateEntry::handle(
            $this->automation,
            $payload,
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        $this->assertNull($second);
        $this->assertEquals(1, Transaction::where('team_id', $this->user->current_team_id)->count());
    }

    #[Test]
    public function it_prevents_reimport_after_transaction_deleted(): void
    {
        Notification::fake();
        $payload = $this->makePayload();

        $transaction = TransactionCreateEntry::handle(
            $this->automation,
            $payload,
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        // Raw delete to avoid model events that try to restore the deleted model
        Transaction::where('id', $transaction->id)->toBase()->delete();
        $this->assertEquals(0, Transaction::where('team_id', $this->user->current_team_id)->count());

        $result = TransactionCreateEntry::handle(
            $this->automation,
            $payload,
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        $this->assertNull($result);
        $this->assertEquals(0, Transaction::where('team_id', $this->user->current_team_id)->count());
    }

    #[Test]
    public function it_detects_duplicate_charge_from_different_email(): void
    {
        $alertPayload = $this->makePayload([
            'id' => 'gmail_alert_001',
            'messageId' => '<alert@bhd.com>',
        ]);

        $first = TransactionCreateEntry::handle(
            $this->automation,
            $alertPayload,
            $this->task,
            $this->previousTask,
            $this->trigger
        );
        $this->assertNotNull($first);

        $notificationPayload = $this->makePayload([
            'id' => 'gmail_notif_001',
            'messageId' => '<notif@bhd.com>',
            'description' => 'Notificacion de compra',
        ]);

        $second = TransactionCreateEntry::handle(
            $this->automation,
            $notificationPayload,
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        $this->assertNotNull($second);
        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Transaction::where('team_id', $this->user->current_team_id)->count());
    }

    #[Test]
    public function it_allows_different_amounts_on_same_day(): void
    {
        $payload1 = $this->makePayload([
            'id' => 'gmail_001',
            'messageId' => '<msg1@bhd.com>',
            'total' => '1500.00',
        ]);

        $payload2 = $this->makePayload([
            'id' => 'gmail_002',
            'messageId' => '<msg2@bhd.com>',
            'total' => '2500.00',
        ]);

        TransactionCreateEntry::handle(
            $this->automation,
            $payload1,
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        TransactionCreateEntry::handle(
            $this->automation,
            $payload2,
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        $this->assertEquals(2, Transaction::where('team_id', $this->user->current_team_id)->count());
    }

    #[Test]
    public function find_duplicate_by_account_signature_returns_match(): void
    {
        $teamId = $this->user->current_team_id;

        Transaction::create([
            'team_id' => $teamId,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'date' => '2026-10-24',
            'total' => 1500,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'description' => 'Original charge',
        ]);

        $service = new TransactionService;
        $match = $service->findDuplicateByAccountSignature([
            'account_id' => $this->account->id,
            'date' => '2026-10-24',
            'total' => 1500,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
        ]);

        $this->assertNotNull($match);
    }

    #[Test]
    public function find_duplicate_by_account_signature_ignores_different_total(): void
    {
        $teamId = $this->user->current_team_id;

        Transaction::create([
            'team_id' => $teamId,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'date' => '2026-10-24',
            'total' => 1500,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'description' => 'A charge',
        ]);

        $service = new TransactionService;
        $match = $service->findDuplicateByAccountSignature([
            'account_id' => $this->account->id,
            'date' => '2026-10-24',
            'total' => 2500,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
        ]);

        $this->assertNull($match);
    }
}
