<?php

namespace App\Domains\Transaction\Services;

use App\Domains\Transaction\Actions\MapBankStatementToLoger;
use App\Domains\Transaction\Actions\ParseBankCsv;
use App\Domains\Transaction\Actions\ParseBankPdf;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Turns a bank statement (PDF, CSV or Excel) into draft transactions on an
 * account, skipping the ones already recorded. Parsing is local: no external
 * service reads the file.
 */
class StatementImportService
{
    public const EXTENSIONS = ['pdf', 'csv', 'txt', 'xlsx', 'xls'];

    /**
     * @return array{found: int, imported: int, skipped: int}
     */
    public function import(UploadedFile $file, Account $account, User $user): array
    {
        $rows = $this->rows($file);
        $session = ['team_id' => $user->current_team_id, 'user_id' => $user->id];
        $transactions = new TransactionService;
        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $transactionData = CreditCardPaymentDetector::apply(MapBankStatementToLoger::parse($row, $session, $account->id));

            $duplicate = $transactions->findDuplicateByBankReference($transactionData)
                ?? $transactions->findIfDuplicated($transactionData);
            if ($duplicate) {
                $skipped++;

                continue;
            }

            Transaction::createTransaction($transactionData);
            $imported++;
        }

        return ['found' => count($rows), 'imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * @return array<int, array{date: string, reference: string, description: string, debit: float, credit: float, balance: float}>
     */
    private function rows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());
        $stored = $file->store('temp');
        $path = storage_path('app').'/'.$stored;

        try {
            return match ($extension) {
                'pdf' => ParseBankPdf::parse($path),
                'xlsx', 'xls' => ParseBankCsv::parseRows(IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false)),
                default => ParseBankCsv::parse($path),
            };
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages(['file' => __('We could not read this statement. Check that it is the file your bank exported.')]);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
