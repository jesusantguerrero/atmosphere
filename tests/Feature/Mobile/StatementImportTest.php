<?php

namespace Tests\Feature\Mobile;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Insane\Journal\Models\Core\AccountDetailType;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StatementImportTest extends TestCase
{
    use RefreshDatabase;

    private const CSV = "Fecha,Descripcion,Debito,Credito,Balance,Referencia\n2026-11-02,SUPERMERCADO NACIONAL,1500.00,,48500.00,REF001\n2026-11-03,PAGO NOMINA,,30000.00,78500.00,REF002\n";

    private User $user;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();

        $this->user = User::factory()->withPersonalTeam()->create();
        $teamId = $this->user->ownedTeams()->first()->id;
        $this->user->forceFill(['current_team_id' => $teamId])->save();
        $this->account = $this->account('Popular', $teamId);
        Sanctum::actingAs($this->user->fresh());
    }

    public function test_a_csv_statement_creates_drafts_once(): void
    {
        $this->postJson('/api/mobile/statements/import', ['file' => $this->csv(), 'account_id' => $this->account->id])
            ->assertOk()
            ->assertJson(['needs_account' => false, 'account_id' => $this->account->id, 'found' => 2, 'imported' => 2, 'skipped' => 0, 'status' => 'draft']);

        $drafts = Transaction::query()->where('account_id', $this->account->id)->where('status', Transaction::STATUS_DRAFT)->get();
        $this->assertCount(2, $drafts);

        $this->postJson('/api/mobile/statements/import', ['file' => $this->csv(), 'account_id' => $this->account->id])
            ->assertOk()
            ->assertJson(['imported' => 0, 'skipped' => 2]);
    }

    public function test_an_excel_statement_is_read_like_a_csv(): void
    {
        $this->postJson('/api/mobile/statements/import', ['file' => $this->xlsx(), 'account_id' => $this->account->id])
            ->assertOk()
            ->assertJson(['found' => 2, 'imported' => 2]);
    }

    public function test_without_an_account_it_asks_which_one(): void
    {
        $this->account('Viejo', $this->user->current_team_id)->forceFill(['closed_at' => now()])->save();

        $this->postJson('/api/mobile/statements/import', ['file' => $this->csv()])
            ->assertOk()
            ->assertJsonPath('needs_account', true)
            ->assertJsonPath('accounts', [['id' => $this->account->id, 'name' => 'Popular', 'currency_code' => 'DOP']]);

        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_rejects_other_households_accounts_and_unsupported_files(): void
    {
        $stranger = User::factory()->withPersonalTeam()->create();
        $theirs = $this->account('Ajena', $stranger->ownedTeams()->first()->id, $stranger);

        $this->postJson('/api/mobile/statements/import', ['file' => $this->csv(), 'account_id' => $theirs->id])
            ->assertUnprocessable()->assertJsonValidationErrors('account_id');
        $this->postJson('/api/mobile/statements/import', ['file' => UploadedFile::fake()->create('foto.jpg', 10, 'image/jpeg'), 'account_id' => $this->account->id])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->postJson('/api/mobile/statements/import', ['account_id' => $this->account->id])
            ->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_a_pdf_without_recognizable_movements_imports_nothing(): void
    {
        $this->postJson('/api/mobile/statements/import', ['file' => UploadedFile::fake()->createWithContent('estado.pdf', "%PDF-1.4\n%%EOF"), 'account_id' => $this->account->id])
            ->assertOk()
            ->assertJson(['found' => 0, 'imported' => 0, 'skipped' => 0]);
    }

    private function account(string $name, int $teamId, ?User $user = null): Account
    {
        return Account::create([
            'team_id' => $teamId,
            'user_id' => ($user ?? $this->user)->id,
            'name' => $name,
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::BANK)->value('id'),
        ]);
    }

    private function csv(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('estado.csv', self::CSV);
    }

    private function xlsx(): UploadedFile
    {
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray(array_map(fn (string $line) => str_getcsv($line), array_filter(explode("\n", self::CSV))));
        $path = tempnam(sys_get_temp_dir(), 'statement').'.xlsx';
        (new Xlsx($sheet))->save($path);

        return new UploadedFile($path, 'estado.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
