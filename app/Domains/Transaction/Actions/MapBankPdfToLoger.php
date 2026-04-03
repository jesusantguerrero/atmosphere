<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\Models\Transaction;
use Insane\Journal\Models\Core\Payee;

class MapBankPdfToLoger
{
    /**
     * Map a parsed BHD PDF transaction row to the Loger transaction format.
     *
     * @param  array{date: string, reference: string, description: string, debit: float, credit: float, balance: float}  $row
     * @param  array{team_id: int, user_id: int}  $session
     */
    public static function parse(array $row, array $session, int $accountId): array
    {
        $isCredit = $row['credit'] > 0;
        $amount = $isCredit ? $row['credit'] : $row['debit'];
        $direction = $isCredit ? Transaction::DIRECTION_DEBIT : Transaction::DIRECTION_CREDIT;

        $description = trim($row['description']);
        $payeeName = self::extractPayeeName($description);
        $payee = Payee::findOrCreateByName($session, $payeeName);

        return [
            'team_id' => $session['team_id'],
            'user_id' => $session['user_id'],
            'account_id' => $accountId,
            'counter_account_id' => $payee->account_id,
            'payee_id' => $payee->id,
            'date' => $row['date'],
            'currency_code' => 'DOP',
            'category_id' => null,
            'description' => $description,
            'direction' => $direction,
            'total' => $amount,
            'reference' => $row['reference'],
            'status' => Transaction::STATUS_DRAFT,
            'items' => [],
            'metaData' => [
                'resource_id' => 'BHD_PDF',
                'resource_origin' => 'BHD_PDF',
                'resource_type' => 'transaction',
            ],
        ];
    }

    private static function extractPayeeName(string $description): string
    {
        // Clean up common BHD description patterns to extract meaningful payee names
        $patterns = [
            '/^Com\.\s*Pago al Inst\.\s*RD$/i' => 'Comisión Pago al Instante',
            '/^Imp\.\s*Art\.\s*12\s*ley\s*288-04$/i' => 'Impuesto Ley 288-04',
            '/^Impuesto\s*Art\.?\s*12\s*Ley\s*No\.?\s*288-04$/i' => 'Impuesto Ley 288-04',
            '/^Pago al Instante\s+(?:Transf\.\s*Ñ|TC\s*Ñ)\s+(.+)$/i' => 'Pago al Instante',
            '/^Compra Divisas\s+Ref\.\s*(.+)$/i' => 'Compra Divisas',
            '/^Pago Intereses CA$/i' => 'Intereses Cuenta Ahorros',
            '/^Reten\.ley\s+253-12\s+10%\s+DGII\s+RD$/i' => 'Retención DGII',
            '/^Mensualidad$/i' => 'Mensualidad',
            '/^Debito por Transferencia$/i' => 'Transferencia',
            '/^PAGO DE TC\s+(.+)$/i' => 'Pago Tarjeta de Crédito',
            '/^ORD:\s*(.+)$/i' => 'Transferencia Recibida',
        ];

        foreach ($patterns as $pattern => $defaultName) {
            if (preg_match($pattern, $description)) {
                return $defaultName;
            }
        }

        // If no pattern matches, use the description itself (truncated)
        $name = $description;
        if (strlen($name) > 100) {
            $name = substr($name, 0, 100);
        }

        return $name ?: 'BHD Transaction';
    }
}
