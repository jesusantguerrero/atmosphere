<?php

namespace Tests\Feature;

use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Integration\Actions\APAP\APAP;
use App\Domains\Integration\Actions\TransactionCreateEntry;
use App\Domains\Integration\Concerns\GenericPayee;
use App\Domains\Transaction\Services\PayeeResolver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Transaction;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailImportAccountsAndTransfersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    private Automation $automation;

    private AutomationTaskAction $trigger;

    private AutomationTaskAction $previousTask;

    private AutomationTaskAction $task;

    private Account $cash;

    private Account $bhdDebit;

    private Account $apapSavings;

    private Account $sirena;

    private int $emailCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $this->user->ownedTeams()->first()->id;
        $this->user->forceFill(['current_team_id' => $this->teamId])->save();

        $this->cash = $this->makeAccount('Cash on hand', null);
        $this->bhdDebit = $this->makeAccount('Personal: No  Debit Card', '0037');
        $this->apapSavings = $this->makeAccount('APAP Ahorro', '5521');
        $this->sirena = $this->makeAccount('APAP Sirena', '0112', true);

        $this->automation = Automation::factory()->create(['user_id' => $this->user->id, 'team_id' => $this->teamId]);
        $common = ['automation_id' => $this->automation->id, 'team_id' => $this->teamId, 'user_id' => $this->user->id];
        $this->trigger = AutomationTaskAction::factory()->create([...$common, 'name' => 'gmailReceived', 'entity' => 'App\\Domains\\Integration\\Actions\\GmailReceived', 'task_type' => 'trigger']);
        $this->previousTask = AutomationTaskAction::factory()->create([...$common, 'name' => 'Parser', 'entity' => 'App\\Domains\\Integration\\Actions\\UniversalBankParser', 'task_type' => 'action']);
        $this->task = AutomationTaskAction::factory()->create([
            ...$common,
            'name' => 'createTransaction',
            'entity' => 'App\\Domains\\Integration\\Actions\\TransactionCreateEntry',
            'task_type' => 'action',
            'values' => json_encode([
                'account_id' => (string) $this->cash->id,
                'description' => '${description}',
                'date' => '${date}',
                'direction' => 'WITHDRAW',
                'total' => '${amount}',
                'currency_code' => '${currencyCode}',
            ]),
        ]);
    }

    private function makeAccount(string $name, ?string $number, bool $card = false): Account
    {
        return Account::forceCreate([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'name' => $name,
            'number' => $number,
            'currency_code' => 'DOP',
            'display_id' => str($name)->slug()->toString(),
            'account_detail_type_id' => 1,
            'credit_closing_day' => $card ? 5 : null,
        ]);
    }

    private function makeCategory(string $name): Category
    {
        return Category::forceCreate([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'name' => $name,
            'display_id' => str($name)->slug()->toString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $this->emailCounter++;

        return array_merge([
            'id' => "gmail_{$this->emailCounter}",
            'messageId' => "<m{$this->emailCounter}@bank>",
            'subject' => "Alerta {$this->emailCounter}",
            'payee' => 'Transacción Aprobada',
            'description' => 'Transferencia',
            'date' => '2026-11-06',
            'amount' => '40000',
            'currencyCode' => 'DOP',
            'productCode' => null,
            'productBrand' => null,
            'productName' => null,
            'destinationProductName' => null,
            'destinationProductCode' => null,
            'transactionType' => null,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function import(array $overrides = []): ?Transaction
    {
        return TransactionCreateEntry::handle($this->automation, $this->payload($overrides), $this->task, $this->previousTask, $this->trigger);
    }

    private function seedPastCharge(int $payeeId, int $categoryId): void
    {
        Transaction::createTransaction([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => $this->cash->id,
            'payee_id' => $payeeId,
            'category_id' => $categoryId,
            'date' => '2026-10-01',
            'description' => 'old',
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => 5,
            'currency_code' => 'DOP',
        ]);
    }

    #[Test]
    public function it_matches_the_account_by_last_digits_even_when_its_name_lacks_the_bank(): void
    {
        $transaction = $this->import(['productCode' => '0037', 'productBrand' => 'BHD']);

        $this->assertSame($this->bhdDebit->id, (int) $transaction->account_id);
    }

    #[Test]
    public function it_matches_an_account_whose_stored_number_ends_with_the_digits(): void
    {
        $this->bhdDebit->forceFill(['number' => '10230037'])->save();

        $transaction = $this->import(['productCode' => 'XXXX0037', 'productBrand' => 'BHD']);

        $this->assertSame($this->bhdDebit->id, (int) $transaction->account_id);
    }

    #[Test]
    public function it_uses_the_brand_to_break_a_tie_between_accounts_sharing_the_digits(): void
    {
        $this->makeAccount('Popular Debit', '0037');
        $this->bhdDebit->forceFill(['name' => 'BHD Debit'])->save();

        $transaction = $this->import(['productCode' => '0037', 'productBrand' => 'BHD']);

        $this->assertSame($this->bhdDebit->id, (int) $transaction->account_id);
    }

    #[Test]
    public function it_falls_back_to_the_default_account_when_the_digits_are_ambiguous_or_unknown(): void
    {
        $this->makeAccount('Another Card', '0037');

        $ambiguous = $this->import(['productCode' => '0037', 'productBrand' => 'BHD', 'amount' => '10']);
        $unknown = $this->import(['productCode' => '9999', 'productBrand' => 'BHD', 'amount' => '11']);

        $this->assertSame($this->cash->id, (int) $ambiguous->account_id);
        $this->assertSame($this->cash->id, (int) $unknown->account_id);
    }

    #[Test]
    public function it_resolves_the_internal_transfer_destination_without_the_brand_in_its_name(): void
    {
        $transaction = $this->import([
            'productCode' => '0037',
            'productBrand' => 'BHD',
            'destinationProductCode' => '5521',
            'transactionType' => 'INTERNAL_TRANSFER',
        ]);

        $this->assertTrue((bool) $transaction->is_transfer);
        $this->assertSame($this->apapSavings->id, (int) $transaction->counter_account_id);
    }

    #[Test]
    public function an_incoming_direction_from_the_parser_is_recorded_as_a_deposit(): void
    {
        $incoming = $this->import(['productCode' => '5521', 'direction' => Transaction::DIRECTION_DEBIT, 'amount' => '500']);
        $unspecified = $this->import(['productCode' => '5521', 'amount' => '501']);

        $this->assertSame(Transaction::DIRECTION_DEBIT, $incoming->direction);
        $this->assertSame(Transaction::DIRECTION_CREDIT, $unspecified->direction);
    }

    #[Test]
    public function apap_detects_incoming_transfers_from_the_email_text(): void
    {
        $this->assertSame(Transaction::DIRECTION_DEBIT, APAP::parseDirection('Transferencia LBTR - Entrante '));
        $this->assertSame(Transaction::DIRECTION_DEBIT, APAP::parseDirection('Depósito recibido'));
        $this->assertNull(APAP::parseDirection('Transferencia LBTR - Saliente'));
        $this->assertNull(APAP::parseDirection('Supermercado Nacional'));
    }

    #[Test]
    public function an_empty_description_falls_back_to_the_payee_text_instead_of_zero(): void
    {
        $transaction = $this->import(['description' => '', 'payee' => 'Transferencia LBTR - Entrante ']);

        $this->assertSame('Transferencia LBTR - Entrante', $transaction->description);
    }

    #[Test]
    public function an_outgoing_and_an_incoming_movement_at_different_banks_become_one_transfer(): void
    {
        $outgoing = $this->import(['productCode' => '0037', 'productBrand' => 'BHD']);
        $paired = $this->import(['productCode' => '5521', 'direction' => Transaction::DIRECTION_DEBIT, 'date' => '2026-11-07']);

        $this->assertSame($outgoing->id, $paired->id);
        $this->assertSame(1, Transaction::where('team_id', $this->teamId)->count());
        $transfer = $outgoing->fresh();
        $this->assertTrue((bool) $transfer->is_transfer);
        $this->assertSame($this->bhdDebit->id, (int) $transfer->account_id);
        $this->assertSame($this->apapSavings->id, (int) $transfer->counter_account_id);
        $this->assertNull($transfer->category_id);
        $this->assertEmpty($transfer->payee_id);
        $this->assertDatabaseHas('processed_emails', ['gmail_id' => 'gmail_2', 'transaction_id' => $outgoing->id, 'status' => 'duplicate']);
    }

    #[Test]
    public function the_incoming_movement_arriving_first_is_still_paired_with_the_outgoing_one(): void
    {
        $incoming = $this->import(['productCode' => '5521', 'direction' => Transaction::DIRECTION_DEBIT]);
        $this->import(['productCode' => '0037', 'productBrand' => 'BHD', 'date' => '2026-11-07']);

        $this->assertSame(1, Transaction::where('team_id', $this->teamId)->count());
        $transfer = $incoming->fresh();
        $this->assertTrue((bool) $transfer->is_transfer);
        $this->assertSame(Transaction::DIRECTION_CREDIT, $transfer->direction);
        $this->assertSame($this->bhdDebit->id, (int) $transfer->account_id);
        $this->assertSame($this->apapSavings->id, (int) $transfer->counter_account_id);
        $this->assertSame('2026-11-07', substr((string) $transfer->date, 0, 10));
    }

    #[Test]
    public function a_card_payment_pairs_the_bank_outflow_with_the_card_inflow(): void
    {
        $this->import(['productCode' => '5521', 'amount' => '29544.83']);
        $this->import(['productCode' => '0112', 'direction' => Transaction::DIRECTION_DEBIT, 'amount' => '29544.83']);

        $this->assertSame(1, Transaction::where('team_id', $this->teamId)->count());
        $transfer = Transaction::where('team_id', $this->teamId)->first();
        $this->assertSame($this->apapSavings->id, (int) $transfer->account_id);
        $this->assertSame($this->sirena->id, (int) $transfer->counter_account_id);
    }

    #[Test]
    public function movements_that_do_not_match_exactly_are_left_unpaired(): void
    {
        $this->import(['productCode' => '0037']);

        $this->import(['productCode' => '5521', 'direction' => Transaction::DIRECTION_DEBIT, 'amount' => '40000.01']);
        $this->import(['productCode' => '5521', 'direction' => Transaction::DIRECTION_DEBIT, 'date' => '2026-11-09']);
        $this->import(['productCode' => '0037', 'direction' => Transaction::DIRECTION_DEBIT]);

        $this->assertSame(4, Transaction::where('team_id', $this->teamId)->count());
        $this->assertSame(0, Transaction::where('team_id', $this->teamId)->where('is_transfer', true)->count());
    }

    #[Test]
    public function two_possible_counterparts_mean_no_pairing(): void
    {
        $this->import(['productCode' => '0037']);
        $this->import(['productCode' => '0112', 'date' => '2026-11-07']);

        $this->import(['productCode' => '5521', 'direction' => Transaction::DIRECTION_DEBIT]);

        $this->assertSame(3, Transaction::where('team_id', $this->teamId)->count());
        $this->assertSame(0, Transaction::where('team_id', $this->teamId)->where('is_transfer', true)->count());
    }

    #[Test]
    public function a_verified_manual_entry_is_not_hijacked_by_pairing(): void
    {
        Transaction::createTransaction([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => $this->bhdDebit->id,
            'counter_account_id' => null,
            'payee_id' => null,
            'date' => '2026-11-06',
            'description' => 'Manual',
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => 40000,
            'currency_code' => 'DOP',
            'status' => Transaction::STATUS_VERIFIED,
        ]);

        $this->import(['productCode' => '5521', 'direction' => Transaction::DIRECTION_DEBIT]);

        $this->assertSame(2, Transaction::where('team_id', $this->teamId)->count());
    }

    #[Test]
    public function a_generic_payee_creates_no_payee_and_inherits_no_category(): void
    {
        $groceries = $this->makeCategory('Groceries');
        $generic = PayeeResolver::resolve($this->automation, 'Transacción Aprobada');
        $this->seedPastCharge($generic->id, $groceries->id);

        $transaction = $this->import(['productCode' => '0112', 'amount' => '100']);

        $this->assertNull($transaction->category_id);
        $this->assertNull($transaction->payee_id);
    }

    #[Test]
    public function a_real_merchant_still_inherits_its_last_category(): void
    {
        $groceries = $this->makeCategory('Groceries');
        $merchant = PayeeResolver::resolve($this->automation, 'Supermercado Nacional');
        $this->seedPastCharge($merchant->id, $groceries->id);

        $transaction = $this->import(['payee' => 'Supermercado Nacional', 'description' => 'Compra', 'amount' => '900']);

        $this->assertSame($groceries->id, (int) $transaction->category_id);
        $this->assertSame($merchant->id, (int) $transaction->payee_id);
    }

    #[Test]
    public function it_recognizes_generic_payee_texts(): void
    {
        foreach (['Transacción Aprobada ', 'Transferencia LBTR - Entrante', 'BHD Notification', '10.00 ', '0', '', '  '] as $name) {
            $this->assertTrue(GenericPayee::matches($name), "'{$name}' should be generic");
        }
        $this->assertTrue(GenericPayee::matches('Jesus Guerrero', 'JESUS GUERRERO'));
        $this->assertFalse(GenericPayee::matches('Jesus Guerrero'));
        $this->assertFalse(GenericPayee::matches('Supermercado Nacional'));
    }
}
