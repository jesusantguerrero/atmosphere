<?php

namespace App\Domains\Transaction\Actions;

use Smalot\PdfParser\Parser;

/**
 * Reads BHD «Detalle de movimientos» PDFs, both layouts:
 *
 * - Accounts (CA/CC): `dd/mm/yyyy<confirmation> description RD$ amount RD$ balance`
 *   — the date is glued to the confirmation number, and long descriptions wrap
 *   onto the next lines with the amounts at the end.
 * - Credit cards (TC): `authorization \t dd/mm/yyyy \t dd/mm/yyyy description RD$ amount`
 *   — one amount, negative for payments and refunds, in RD$ or US$.
 */
class ParseBankPdf
{
    private const AMOUNT = '(RD|US)\$\s*(-?[\d,]+\.\d{2})';

    /**
     * @return array<int, array{date: string, reference: string, description: string, debit: float, credit: float, balance: float, currency?: string}>
     */
    public static function parse(string $filePath): array
    {
        return self::parseText((new Parser)->parseFile($filePath)->getText());
    }

    /**
     * @return array<int, array{date: string, reference: string, description: string, debit: float, credit: float, balance: float, currency?: string}>
     */
    public static function parseText(string $text): array
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $text)),
            fn (string $line) => $line !== '' && ! self::isHeaderOrFooter($line),
        ));

        if (preg_match('/Fecha\tRef\.\tDetalles/', $text)) {
            return self::monthlyStatementRows($lines, $text);
        }

        return str_contains($text, 'Tarjetas de crédito')
            ? self::creditCardRows($lines)
            : self::accountRows($lines);
    }

    /**
     * Monthly «Estado de cuenta»: `dd/mm/yyyy \t ref \t details \t debit \t credit \t balance`,
     * plain numbers; the account currency is printed once in the header («Moneda»).
     *
     * @param  list<string>  $lines
     * @return array<int, array{date: string, reference: string, description: string, debit: float, credit: float, balance: float, currency: string}>
     */
    private static function monthlyStatementRows(array $lines, string $text): array
    {
        $currency = preg_match('/Moneda\s*\n\s*(RD|US)\$/', $text, $match) ? self::currency($match[1]) : 'DOP';
        $number = '(-?[\d,]+\.\d{2})';
        $transactions = [];

        foreach ($lines as $line) {
            if (preg_match("/^(\d{2}\/\d{2}\/\d{4})\t(\d+)\t(.+?)\t{$number}\t{$number}\t{$number}$/", $line, $row)) {
                $transactions[] = [
                    'date' => self::convertDate($row[1]),
                    'reference' => $row[2],
                    'description' => trim($row[3]),
                    'debit' => self::parseAmount($row[4]),
                    'credit' => self::parseAmount($row[5]),
                    'balance' => self::parseAmount($row[6]),
                    'currency' => $currency,
                ];
            }
        }

        return $transactions;
    }

    /**
     * @param  list<string>  $lines
     * @return array<int, array{date: string, reference: string, description: string, debit: float, credit: float, balance: float, currency: string}>
     */
    private static function accountRows(array $lines): array
    {
        $rawRows = [];
        foreach (self::records($lines, '/^(\d{2}\/\d{2}\/\d{4})\s*(\d+)\s*(.*)$/', 2) as [$start, $body]) {
            preg_match_all('/'.self::AMOUNT.'/', $body, $amounts);
            $rawRows[] = [
                'date' => self::convertDate($start[1]),
                'reference' => $start[2],
                'description' => self::description($body),
                'amount' => abs(self::parseAmount($amounts[2][0])),
                'balance' => self::parseAmount(end($amounts[2])),
                'currency' => self::currency($amounts[1][0]),
            ];
        }

        // Debit or credit comes from the running balance. Rows are newest first:
        // a credit means balance = next row's balance + amount.
        $transactions = [];
        foreach ($rawRows as $i => $row) {
            $isCredit = isset($rawRows[$i + 1])
                ? abs($row['balance'] - ($rawRows[$i + 1]['balance'] + $row['amount'])) < 0.02
                : self::looksLikeCredit($row['description']);

            $transactions[] = [
                'date' => $row['date'],
                'reference' => $row['reference'],
                'description' => $row['description'],
                'debit' => $isCredit ? 0.0 : $row['amount'],
                'credit' => $isCredit ? $row['amount'] : 0.0,
                'balance' => $row['balance'],
                'currency' => $row['currency'],
            ];
        }

        return $transactions;
    }

    /**
     * @param  list<string>  $lines
     * @return array<int, array{date: string, reference: string, description: string, debit: float, credit: float, balance: float, currency: string}>
     */
    private static function creditCardRows(array $lines): array
    {
        $transactions = [];
        foreach (self::records($lines, '/^(\d*)\s*(\d{2}\/\d{2}\/\d{4})\s+(\d{2}\/\d{2}\/\d{4})\s*(.*)$/', 1) as [$start, $body]) {
            preg_match_all('/'.self::AMOUNT.'/', $body, $amounts);
            $amount = self::parseAmount($amounts[2][0]);

            $transactions[] = [
                'date' => self::convertDate($start[2]),
                'reference' => $start[1],
                'description' => self::description($body),
                'debit' => $amount > 0 ? $amount : 0.0,
                'credit' => $amount < 0 ? abs($amount) : 0.0,
                'balance' => 0.0,
                'currency' => self::currency($amounts[1][0]),
            ];
        }

        return $transactions;
    }

    /**
     * Group a movement's first line with the wrapped lines that follow it, until
     * the group holds the amounts the layout expects.
     *
     * @param  list<string>  $lines
     * @return list<array{0: array<int, string>, 1: string}>
     */
    private static function records(array $lines, string $startPattern, int $amountsNeeded): array
    {
        $records = [];
        $start = null;
        $body = '';

        foreach ($lines as $line) {
            if (preg_match($startPattern, $line, $matches)) {
                $start = $matches;
                $body = (string) end($matches);
            } elseif ($start !== null) {
                $body .= ' '.$line;
            } else {
                continue;
            }

            if (preg_match_all('/'.self::AMOUNT.'/', $body) >= $amountsNeeded) {
                $records[] = [$start, $body];
                $start = null;
                $body = '';
            }
        }

        return $records;
    }

    private static function description(string $body): string
    {
        $firstAmount = preg_match('/'.self::AMOUNT.'/', $body, $match, PREG_OFFSET_CAPTURE) ? $match[0][1] : strlen($body);

        return trim(preg_replace('/\s+/', ' ', substr($body, 0, $firstAmount)));
    }

    private static function currency(string $symbol): string
    {
        return $symbol === 'US' ? 'USD' : 'DOP';
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
            'Nº de tarjeta',
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
            'Fecha de Aplicación',
            'No. Autorización',
            'XXXXXXX',
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($line, $pattern)) {
                return true;
            }
        }

        return in_array($line, ['Fecha de', 'Transacción'], true);
    }
}
