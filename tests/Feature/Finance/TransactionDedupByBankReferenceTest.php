<?php

namespace Tests\Feature\Finance;

use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\TransactionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionDedupByBankReferenceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $service;

    private User $user;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TransactionService;
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->user->current_team_id = $this->user->ownedTeams->first()->id;
        $this->user->save();

        $this->accountId = 1001;
    }

    public function test_matches_existing_row_by_account_reference_total_direction_and_date(): void
    {
        $this->seedTransaction([
            'date' => '2026-04-17',
            'total' => 20478.01,
            'reference' => '3430303',
        ]);

        $incoming = $this->bankImportPayload(reference: '3430303', total: 20478.01, description: 'Pago al Instante TC');

        $this->assertNotNull($this->service->findDuplicateByBankReference($incoming));
    }

    public function test_does_not_match_when_same_reference_but_different_amount(): void
    {
        $this->seedTransaction([
            'date' => '2026-04-17',
            'total' => 20478.01,
            'reference' => '3430303',
        ]);

        $taxEntry = $this->bankImportPayload(reference: '3430303', total: 30.71, description: 'Imp. Art. 12 ley 288-04');

        $this->assertNull($this->service->findDuplicateByBankReference($taxEntry));
    }

    public function test_ignores_payee_differences_across_reimports(): void
    {
        $this->seedTransaction([
            'payee_id' => 555,
            'date' => '2026-04-15',
            'total' => 180.00,
            'reference' => '9193836',
            'description' => 'GUERRERO ALVAREZ-Pago TC Caribe',
        ]);

        $reimport = $this->bankImportPayload(
            reference: '9193836',
            total: 180.00,
            description: 'GUERRERO ALVAREZ-Pago TC Caribe',
            date: '2026-04-15',
            payeeId: 999,
        );

        $this->assertNotNull($this->service->findDuplicateByBankReference($reimport));
    }

    public function test_returns_null_when_reference_missing(): void
    {
        $payload = $this->bankImportPayload(reference: '', total: 100, description: 'manual');

        $this->assertNull($this->service->findDuplicateByBankReference($payload));
    }

    public function test_scoped_to_account(): void
    {
        $this->seedTransaction([
            'date' => '2026-04-17',
            'total' => 20478.01,
            'reference' => '3430303',
        ]);

        $otherAccount = $this->bankImportPayload(reference: '3430303', total: 20478.01, description: 'Pago al Instante TC');
        $otherAccount['account_id'] = $this->accountId + 1;

        $this->assertNull($this->service->findDuplicateByBankReference($otherAccount));
    }

    private function seedTransaction(array $overrides): Transaction
    {
        $transaction = new Transaction;
        $transaction->forceFill(array_merge([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->accountId,
            'date' => '2026-04-17',
            'currency_code' => 'DOP',
            'description' => 'Pago al Instante TC',
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => 20478.01,
            'reference' => '3430303',
            'status' => Transaction::STATUS_DRAFT,
        ], $overrides))->save();

        return $transaction;
    }

    private function bankImportPayload(
        string $reference,
        float $total,
        string $description,
        string $date = '2026-04-17',
        ?int $payeeId = null,
    ): array {
        return [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->accountId,
            'payee_id' => $payeeId,
            'date' => $date,
            'currency_code' => 'DOP',
            'description' => $description,
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => $total,
            'reference' => $reference,
            'status' => Transaction::STATUS_DRAFT,
        ];
    }
}
