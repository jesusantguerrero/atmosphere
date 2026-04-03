<?php

namespace App\Domains\Transaction\Actions;

use Smalot\PdfParser\Parser;

class ParseBankPdf
{
    /**
     * Parse a BHD bank statement PDF and extract transaction rows.
     *
     * @return array<int, array{date: string, reference: string, description: string, debit: float, credit: float, balance: float}>
     */
    public static function parse(string $filePath): array
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        return self::extractTransactions($text);
    }

    /**
     * @return array<int, array{date: string, reference: string, description: string, debit: float, credit: float, balance: float}>
     */
    private static function extractTransactions(string $text): array
    {
        $lines = explode("\n", $text);
        $rawRows = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || self::isHeaderOrFooter($line)) {
                continue;
            }

            // Match transaction lines: dd/mm/yyyy  reference  description  RD$ amount  RD$ balance
            if (preg_match('/^(\d{2}\/\d{2}\/\d{4})\s+(\d+)\s+(.+)/', $line, $matches)) {
                $date = $matches[1];
                $reference = $matches[2];
                $remainder = trim($matches[3]);

                // Extract all RD$ amounts from the line
                preg_match_all('/RD\$\s*([\d,]+\.\d{2})/', $remainder, $amountMatches);

                if (count($amountMatches[0]) >= 2) {
                    // Description is everything before the first RD$ occurrence
                    $firstAmountPos = strpos($remainder, 'RD$');
                    $description = trim(substr($remainder, 0, $firstAmountPos));

                    $amount = self::parseAmount($amountMatches[1][0]);
                    $balance = self::parseAmount($amountMatches[1][count($amountMatches[1]) - 1]);

                    $rawRows[] = [
                        'date' => self::convertDate($date),
                        'reference' => $reference,
                        'description' => $description,
                        'amount' => $amount,
                        'balance' => $balance,
                    ];
                }
            }
        }

        // Determine debit/credit by comparing consecutive balances.
        // Rows are in reverse chronological order (newest first).
        // For row i: if balance_i = next_row_balance + amount → credit (money in)
        //            if balance_i = next_row_balance - amount → debit (money out)
        $transactions = [];
        $rowCount = count($rawRows);

        for ($i = 0; $i < $rowCount; $i++) {
            $row = $rawRows[$i];
            $amount = $row['amount'];
            $balance = $row['balance'];

            if ($i < $rowCount - 1) {
                $previousBalance = $rawRows[$i + 1]['balance'];
                $isCredit = abs($balance - ($previousBalance + $amount)) < 0.02;
            } else {
                // Last row (oldest transaction): infer from known patterns
                $isCredit = self::looksLikeCredit($row['description']);
            }

            $transactions[] = [
                'date' => $row['date'],
                'reference' => $row['reference'],
                'description' => $row['description'],
                'debit' => $isCredit ? 0 : $amount,
                'credit' => $isCredit ? $amount : 0,
                'balance' => $balance,
            ];
        }

        return $transactions;
    }

    private static function parseAmount(string $amount): float
    {
        return (float) str_replace(',', '', $amount);
    }

    private static function convertDate(string $date): string
    {
        $parts = explode('/', $date);

        return "{$parts[2]}-{$parts[1]}-{$parts[0]}";
    }

    private static function looksLikeCredit(string $description): bool
    {
        $creditPatterns = [
            '/^Pago Intereses/i',
            '/^ORD:/i',
            '/^Compra Divisas/i',
            '/^Transferencia recibida/i',
            '/^Deposito/i',
            '/^Abono/i',
        ];

        foreach ($creditPatterns as $pattern) {
            if (preg_match($pattern, $description)) {
                return true;
            }
        }

        return false;
    }

    private static function isHeaderOrFooter(string $line): bool
    {
        $skipPatterns = [
            'Detalle de movimientos',
            'N° de cuenta',
            'Nº de cuenta',
            'Titular de la cuenta',
            'Documento de identidad',
            'Fecha y hora de generación',
            'Rango de movimientos',
            'Desde:',
            'Página',
            'Banco Múltiple',
            'C/ Luis F.',
            'Tel.:',
            'Fecha Nº confirmación',
            'XXXXXXX',
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($line, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
