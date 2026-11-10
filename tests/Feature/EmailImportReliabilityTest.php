<?php

namespace Tests\Feature;

use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Integration\Actions\TransactionCreateEntry;
use App\Domains\Transaction\Services\PayeeResolver;
use App\Domains\Transaction\Services\TransactionService;
use App\Models\ProcessedEmail;
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

        $imported = Transaction::create([
            'team_id' => $teamId,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'date' => '2026-10-24',
            'total' => 1500,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'description' => 'Original charge',
        ]);
        ProcessedEmail::record($teamId, 'gmail_alert', null, $this->automation->id, $imported->id);

        $service = new TransactionService;
        $match = $service->findDuplicateByAccountSignature([
            'account_id' => $this->account->id,
            'date' => '2026-10-24',
            'total' => 1500,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
        ]);

        $this->assertSame($imported->id, $match?->id);
    }

    #[Test]
    public function a_manual_entry_of_the_same_amount_does_not_swallow_the_bank_email(): void
    {
        Transaction::create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'date' => '2026-10-24',
            'total' => 1500,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'description' => 'Entered by hand',
        ]);

        $result = TransactionCreateEntry::handle(
            $this->automation,
            $this->makePayload(['payee' => 'La Sirena', 'description' => 'Compra POS']),
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        $this->assertSame('La Sirena - Compra POS', $result->description);
        $this->assertSame(2, Transaction::where('team_id', $this->user->current_team_id)->count());
        $this->assertSame('processed', ProcessedEmail::where('gmail_id', 'gmail_msg_001')->value('status'));
    }

    #[Test]
    public function a_purchase_entered_by_hand_at_the_same_merchant_absorbs_the_bank_email(): void
    {
        $payee = PayeeResolver::resolve($this->automation, 'Shell La Romana');
        $manual = Transaction::create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'payee_id' => $payee->id,
            'date' => '2026-10-24',
            'total' => 1500,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'description' => 'gasolina 7 galones',
        ]);

        $result = TransactionCreateEntry::handle(
            $this->automation,
            $this->makePayload(['payee' => 'Shell La Romana', 'description' => 'Tarjeta crédito Qik']),
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        $this->assertSame($manual->id, $result->id);
        $this->assertSame(1, Transaction::where('team_id', $this->user->current_team_id)->count());
        $this->assertSame('duplicate', ProcessedEmail::where('gmail_id', 'gmail_msg_001')->value('status'));
    }

    #[Test]
    public function an_email_linked_to_another_emails_charge_is_recorded_as_duplicate(): void
    {
        $first = TransactionCreateEntry::handle($this->automation, $this->makePayload(['id' => 'gmail_alert']), $this->task, $this->previousTask, $this->trigger);
        $second = TransactionCreateEntry::handle($this->automation, $this->makePayload(['id' => 'gmail_notif', 'messageId' => '<n@bhd.com>', 'description' => 'Notificacion']), $this->task, $this->previousTask, $this->trigger);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('duplicate', ProcessedEmail::where('gmail_id', 'gmail_notif')->value('status'));
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

    #[Test]
    public function emails_whose_gmail_ids_share_a_leading_digit_are_imported_separately(): void
    {
        $first = TransactionCreateEntry::handle($this->automation, $this->makePayload(['id' => '1a09b686e1403129', 'messageId' => '<a@qik.do>']), $this->task, $this->previousTask, $this->trigger);
        $second = TransactionCreateEntry::handle($this->automation, $this->makePayload(['id' => '1a0e4c2b77d10f3a', 'messageId' => '<b@qik.do>', 'payee' => 'La Sirena', 'total' => '6574.03']), $this->task, $this->previousTask, $this->trigger);

        $this->assertNotNull($second);
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, ProcessedEmail::count());
    }

    #[Test]
    public function two_identical_purchases_at_the_same_store_are_both_imported(): void
    {
        $coffee = ['payee' => 'Cafe Ole', 'total' => '150.00', 'subject' => 'Usaste tu tarjeta de crédito Qik'];

        $first = $this->importEmail(['id' => 'gmail_cafe_1', 'messageId' => '<c1@qik.do>', ...$coffee]);
        $second = $this->importEmail(['id' => 'gmail_cafe_2', 'messageId' => '<c2@qik.do>', ...$coffee]);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, Transaction::where('team_id', $this->user->current_team_id)->count());
    }

    #[Test]
    public function an_alert_and_a_notification_of_one_charge_stay_one_transaction(): void
    {
        $alert = $this->importEmail(['id' => 'gmail_alert', 'messageId' => '<a@bhd.com.do>', 'subject' => 'Alertas BHD']);
        $notification = $this->importEmail(['id' => 'gmail_notif', 'messageId' => '<n@bhd.com.do>', 'description' => 'Notificacion', 'subject' => 'BHD Notificación de Transacciones']);

        $this->assertSame($alert->id, $notification->id);
        $this->assertSame(1, Transaction::where('team_id', $this->user->current_team_id)->count());
    }

    #[Test]
    public function a_hand_entered_purchase_takes_one_email_and_the_next_identical_one_is_a_new_purchase(): void
    {
        $payee = PayeeResolver::resolve($this->automation, 'Cafe Ole');
        $manual = Transaction::create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'payee_id' => $payee->id,
            'date' => '2026-10-24',
            'total' => 150,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'description' => 'cafe',
        ]);
        $coffee = ['payee' => 'Cafe Ole', 'total' => '150.00', 'subject' => 'Usaste tu tarjeta de crédito Qik'];

        $first = $this->importEmail(['id' => 'gmail_cafe_1', 'messageId' => '<c1@qik.do>', ...$coffee]);
        $second = $this->importEmail(['id' => 'gmail_cafe_2', 'messageId' => '<c2@qik.do>', ...$coffee]);

        $this->assertSame($manual->id, $first->id);
        $this->assertNotSame($manual->id, $second->id);
        $this->assertSame(2, Transaction::where('team_id', $this->user->current_team_id)->count());
        $this->assertSame('Usaste tu tarjeta de crédito Qik', ProcessedEmail::where('gmail_id', 'gmail_cafe_1')->value('subject'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function importEmail(array $overrides): mixed
    {
        return TransactionCreateEntry::handle($this->automation, $this->makePayload($overrides), $this->task, $this->previousTask, $this->trigger);
    }
}
