<?php

namespace App\Domains\Transaction\Actions;

class ParseBankCsv
{
    private const DELIMITER_CANDIDATES = [',', ';', "\t"];

    private const COLUMN_ALIASES = [
        'date' => ['date', 'fecha', 'transaction date', 'trans date', 'value date'],
        'description' => ['description', 'descripcion', 'descripción', 'concept', 'concepto', 'detail', 'detalle', 'memo', 'narrative', 'particulars'],
        'debit' => ['debit', 'debito', 'débito', 'withdrawal', 'withdrawals', 'cargo', 'cargos', 'out', 'amount debit', 'debit amount'],
        'credit' => ['credit', 'credito', 'crédito', 'deposit', 'deposits', 'abono', 'abonos', 'in', 'amount credit', 'credit amount'],
        'balance' => ['balance', 'saldo', 'running balance', 'closing balance'],
        'reference' => ['reference', 'referencia', 'ref', 'ref.', 'transaction id', 'txn id', 'check number', 'cheque'],
    ];

    /**
     * Parse a CSV bank statement and extract transaction rows.
     *
     * @return array<int, array{date: string, reference: string, description: string, debit: float, credit: float, balance: float}>
     */
    public static function parse(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return [];
        }

        $rawLines = [];
        while (($line = fgets($handle)) !== false) {
            $rawLines[] = rtrim($line, "\r\n");
        }
        fclose($handle);

        if (empty($rawLines)) {
            return [];
        }

        $delimiter = self::detectDelimiter($rawLines);
        $rows = self::parseLines($rawLines, $delimiter);

        if (count($rows) < 2) {
            return [];
        }

        $columnMap = self::detectColumns($rows[0]);

        if (! isset($columnMap['date']) || ! isset($columnMap['description'])) {
            return [];
        }

        return self::extractTransactions(array_slice($rows, 1), $columnMap);
    }

    private static function detectDelimiter(array $lines): string
    {
        $sampleLines = array_slice($lines, 0, 5);

        $counts = [];
        foreach (self::DELIMITER_CANDIDATES as $delimiter) {
            $counts[$delimiter] = 0;
            foreach ($sampleLines as $line) {
                $counts[$delimiter] += substr_count($line, $delimiter);
            }
        }

        arsort($counts);

        return array_key_first($counts);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private static function parseLines(array $lines, string $delimiter): array
    {
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = str_getcsv($line, $delimiter);
            $rows[] = array_map('trim', $row);
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array<string, int>
     */
    private static function detectColumns(array $headerRow): array
    {
        $columnMap = [];

        foreach ($headerRow as $index => $header) {
            $normalized = strtolower(trim($header));

            foreach (self::COLUMN_ALIASES as $field => $aliases) {
                if (isset($columnMap[$field])) {
                    continue;
                }
                if (in_array($normalized, $aliases, true)) {
                    $columnMap[$field] = $index;
                }
            }
        }

        return $columnMap;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @param  array<string, int>  $columnMap
     * @return array<int, array{date: string, reference: string, description: string, debit: float, credit: float, balance: float}>
     */
    private static function extractTransactions(array $rows, array $columnMap): array
    {
        $transactions = [];

        foreach ($rows as $row) {
            $dateRaw = $row[$columnMap['date']] ?? '';

            if (empty(trim($dateRaw))) {
                continue;
            }

            $date = self::parseDate($dateRaw);

            if ($date === null) {
                continue;
            }

            $description = isset($columnMap['description']) ? ($row[$columnMap['description']] ?? '') : '';
            $reference = isset($columnMap['reference']) ? ($row[$columnMap['reference']] ?? '') : '';
            $debit = isset($columnMap['debit']) ? self::parseAmount($row[$columnMap['debit']] ?? '') : 0.0;
            $credit = isset($columnMap['credit']) ? self::parseAmount($row[$columnMap['credit']] ?? '') : 0.0;
            $balance = isset($columnMap['balance']) ? self::parseAmount($row[$columnMap['balance']] ?? '') : 0.0;

            // Some banks use a single "amount" column with negative = debit
            if (! isset($columnMap['debit']) && ! isset($columnMap['credit']) && isset($columnMap['balance'])) {
                continue;
            }

            $transactions[] = [
                'date' => $date,
                'reference' => $reference,
                'description' => trim($description),
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];
        }

        return $transactions;
    }

    private static function parseAmount(string $value): float
    {
        if (trim($value) === '' || trim($value) === '-') {
            return 0.0;
        }

        // Remove currency symbols and whitespace
        $cleaned = preg_replace('/[^\d.,\-]/', '', $value);

        if ($cleaned === null || $cleaned === '' || $cleaned === '-') {
            return 0.0;
        }

        // Determine decimal separator: if both . and , exist, the last one is the decimal
        $lastComma = strrpos($cleaned, ',');
        $lastDot = strrpos($cleaned, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                // European format: 1.234,56
                $cleaned = str_replace('.', '', $cleaned);
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                // US format: 1,234.56
                $cleaned = str_replace(',', '', $cleaned);
            }
        } elseif ($lastComma !== false) {
            // Could be 1,234 (thousands) or 1,23 (decimal) — assume decimal if <= 2 digits follow
            $afterComma = substr($cleaned, $lastComma + 1);
            if (strlen($afterComma) <= 2) {
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                $cleaned = str_replace(',', '', $cleaned);
            }
        }

        return (float) $cleaned;
    }

    private static function parseDate(string $value): ?string
    {
        $value = trim($value);

        // Y-m-d
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        // d/m/Y or d-m-Y
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})/', $value, $m)) {
            // Disambiguate d/m/Y vs m/d/Y: if second part > 12 it must be day
            if ((int) $m[2] > 12) {
                // m/d/Y
                return "{$m[3]}-{$m[1]}-{$m[2]}";
            }

            // Assume d/m/Y (most common outside US)
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        // m/d/Y (US) — first part > 12 means it is actually d/m/Y handled above
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $value, $m)) {
            return "{$m[3]}-".str_pad($m[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($m[2], 2, '0', STR_PAD_LEFT);
        }

        // Y/m/d
        if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})/', $value, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return null;
    }
}
