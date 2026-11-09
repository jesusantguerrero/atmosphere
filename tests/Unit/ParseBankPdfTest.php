<?php

namespace Tests\Unit;

use App\Domains\Transaction\Actions\ParseBankPdf;
use PHPUnit\Framework\TestCase;

/**
 * Text as Smalot\PdfParser extracts it from real BHD «Detalle de movimientos»
 * PDFs (layout copied from November 2026 statements; names and amounts invented).
 */
class ParseBankPdfTest extends TestCase
{
    private const ACCOUNT = "Detalle de movimientos | Cuenta de ahorros\n"
        ."XXXXXXX0037N° de cuenta:\n"
        ."Titular de la cuenta: \nCLIENTE PRUEBA\nDocumento de identidad: \n00000000000\n"
        ."Fecha y hora de generación: \n05/11/2026 | 01:19 PM\n"
        ."Rango de movimientos: \n06/10/2026 al 05/11/2026\n"
        ."Fecha Nº confirmaciónDescripción\tDébitos Créditos Balance\n"
        ."04/11/20261829351 Imp. transferencia o cheque\tRD$ 49.99\tRD$ 82,899.14\n"
        ."04/11/20261829351 Pago Tienda\tRD$ 1,000.00\tRD$ 82,949.13\n"
        ."03/11/20261428308 Compra Divisas Ref. 2600993858\tRD$ 2,000.00RD$ 83,949.13\n"
        ."Página 1/2\nBanco Múltiple BHD\nC/ Luis F. Thomen, Santo Domingo, República Dominicana\nTel.: 809-243-5000 * Site: https://bhd.com.do\n\n"
        ."Detalle de movimientos | Cuenta de ahorros\nXXXXXXX0037N° de cuenta:\n"
        ."Fecha Nº confirmaciónDescripción\tDébitos Créditos Balance\n"
        ."13/10/20260087112\nTRANSFERENCIA RECIBIDA DE PERSONA\nAPELLIDO\nRD$ 6,000.00RD$ 81,949.13\n"
        ."12/10/20269140476 Debito por Transferencia\tRD$ 500.00\tRD$ 75,949.13\n"
        ."Página 2/2\nBanco Múltiple BHD\n";

    private const CARD = "Detalle de movimientos | Tarjetas de crédito\n"
        ."464133XXXXXX1945Nº de tarjeta:\n"
        ."Fecha y hora de generación: \n04/11/2026 | 02:36 PM\n"
        ."Rango de movimientos: \n05/10/2026 al 04/11/2026\n"
        ."No. Autorización\nFecha de\nTransacción\nFecha de AplicaciónDescripción\tDébitos Créditos\n"
        ."004892\t02/11/2026\t03/11/2026 PedidosYa*Restaurante RD$ 435.00\n"
        ."011024\t31/10/2026\t01/11/2026 EDEESTE IB BHD\tRD$ 1,145.88\n"
        ."\t30/10/2026\t30/10/2026\nPAGO DEBITO A CUENTA\nMBP\nUS$ -185.49\n"
        ."000000\t21/10/2026\t21/10/2026 AHORRO MI PAIS\tRD$ -111.00\n"
        ."065281\t15/10/2026\t16/10/2026 NETFLIX.COM US$ 15.49\n"
        ."Página 1/1\nBanco Múltiple BHD\n";

    public function test_account_statement_reads_glued_confirmation_numbers_and_wrapped_descriptions(): void
    {
        $rows = ParseBankPdf::parseText(self::ACCOUNT);

        $this->assertCount(5, $rows);
        $this->assertSame(['date' => '2026-11-04', 'reference' => '1829351', 'description' => 'Imp. transferencia o cheque', 'debit' => 49.99, 'credit' => 0.0, 'balance' => 82899.14, 'currency' => 'DOP'], $rows[0]);
        $this->assertSame(2000.0, $rows[2]['credit']);
        $this->assertSame('TRANSFERENCIA RECIBIDA DE PERSONA APELLIDO', $rows[3]['description']);
        $this->assertSame('2026-10-13', $rows[3]['date']);
        $this->assertSame(6000.0, $rows[3]['credit']);
        $this->assertSame(500.0, $rows[4]['debit']);
    }

    public function test_card_statement_reads_one_amount_per_row_with_payments_as_credits_and_dollars(): void
    {
        $rows = ParseBankPdf::parseText(self::CARD);

        $this->assertCount(5, $rows);
        $this->assertSame(['date' => '2026-11-02', 'reference' => '004892', 'description' => 'PedidosYa*Restaurante', 'debit' => 435.0, 'credit' => 0.0, 'balance' => 0.0, 'currency' => 'DOP'], $rows[0]);
        $this->assertSame(['PAGO DEBITO A CUENTA MBP', 185.49, 'USD', ''], [$rows[2]['description'], $rows[2]['credit'], $rows[2]['currency'], $rows[2]['reference']]);
        $this->assertSame(111.0, $rows[3]['credit']);
        $this->assertSame(['NETFLIX.COM', 15.49, 'USD'], [$rows[4]['description'], $rows[4]['debit'], $rows[4]['currency']]);
    }

    public function test_monthly_statement_reads_the_table_and_the_header_currency(): void
    {
        $text = "Documento emitido por el Banco Múltiple BHD S.A.\nNúmero de cuenta\nXXXXXXX-004-5\nMoneda\nUS$\nFecha de corte\n31/07/2026\n"
            ."Fecha\tRef.\tDetalles\tDébitos\tCréditos\tBalance\n"
            ."Balance inicial: 01/07/2026 5,393.41\n"
            ."01/07/2026\t85624\tCompra Divisas Ref. 2600588571\t2,500.00\t0.00\t2,893.41\n"
            ."06/07/2026\t1414233\tCR TR INTL: EMPRESA, INC. USD TRA\t0.00\t3,491.25\t6,384.66\n"
            ."0 CKS\t6 DEBITOS\t3,884.68\n";

        $rows = ParseBankPdf::parseText($text);

        $this->assertCount(2, $rows);
        $this->assertSame(['date' => '2026-07-01', 'reference' => '85624', 'description' => 'Compra Divisas Ref. 2600588571', 'debit' => 2500.0, 'credit' => 0.0, 'balance' => 2893.41, 'currency' => 'USD'], $rows[0]);
        $this->assertSame(3491.25, $rows[1]['credit']);
    }

    public function test_text_without_movements_gives_nothing(): void
    {
        $this->assertSame([], ParseBankPdf::parseText("Detalle de movimientos | Cuenta de ahorros\nPágina 1/1\n"));
    }
}
