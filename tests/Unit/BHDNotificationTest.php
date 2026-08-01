<?php

namespace Tests\Unit;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Actions\BHD;
use App\Domains\Integration\Actions\BHDNotification;
use App\Domains\Integration\Concerns\TransactionDataDTO;
use PHPUnit\Framework\TestCase;

class BHDNotificationTest extends TestCase
{
    public function test_it_parses_transfers_between_bhd_products(): void
    {
        $html = <<<'HTML'
        <table>
            <tr><td id="prefix-idProductoOrigen"><strong>DO21BCBH000000000XXXXXXX0037</strong></td></tr>
            <tr><td id="prefix-idProductoDestino"><strong>XXXXXXXXXXXX1945</strong></td></tr>
            <tr><td id="prefix-idDescripcion"><strong></strong></td></tr>
            <tr><td id="prefix-idMonto"><strong>RD$ 5,219.86</strong></td></tr>
            <tr><td id="prefix-idBeneficiario"><strong>JESUS GUERRERO</strong></td></tr>
            <tr><td id="prefix-idNumeroConfirmacion"><strong>M02-1785-1611-3034-5</strong></td></tr>
            <tr><td id="prefix-idFechayHoraTransaccion"><strong>27/07/2026 - 10:24 AM</strong></td></tr>
            <tr><td id="prefix-idTipoTransaccion"><strong>Transacciones entre mis productos</strong></td></tr>
        </table>
        HTML;

        $transaction = (new BHDNotification)->handle(new Automation, [
            'id' => 123,
            'messageId' => 'bhd-transfer-test',
            'message' => $html,
        ]);

        $this->assertSame('0037', $transaction->productCode);
        $this->assertSame('XXXXXXXXXXXX1945', $transaction->destinationProductName);
        $this->assertSame('1945', $transaction->destinationProductCode);
        $this->assertSame('JESUS GUERRERO', $transaction->payee);
        $this->assertSame(TransactionDataDTO::TYPE_INTERNAL_TRANSFER, $transaction->transactionType);
    }

    public function test_it_recognizes_bhd_internal_transfer_type(): void
    {
        $this->assertSame(
            TransactionDataDTO::TYPE_INTERNAL_TRANSFER,
            BHD::parseTransactionType('Transacciones entre mis productos')
        );
    }
}
