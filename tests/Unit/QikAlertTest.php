<?php

namespace Tests\Unit;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Actions\Qik\QikAlert;
use Tests\TestCase;

class QikAlertTest extends TestCase
{
    private function buildMail(string $amount): array
    {
        $html = <<<HTML
        <html><body>
            <p>Usaste tu tarjeta crédito Qik que termina en ****1234.</p>
            <p>Se realizó una transacción de RD\$ {$amount}</p>
            <p>Localidad SUPER MARKET XYZ Fecha y hora 06-13-2026 03:45 PM</p>
        </body></html>
        HTML;

        return [
            'id' => 99,
            'message' => $html,
            'date' => '2026-06-13',
        ];
    }

    /** @test */
    public function it_preserves_decimals_in_the_parsed_amount(): void
    {
        $transaction = (new QikAlert)->handle(new Automation, $this->buildMail('395.90'));

        $this->assertNotNull($transaction);
        $this->assertEquals('395.9', $transaction->amount);
    }

    /** @test */
    public function it_keeps_thousands_separated_decimal_amounts(): void
    {
        $transaction = (new QikAlert)->handle(new Automation, $this->buildMail('1,234.56'));

        $this->assertNotNull($transaction);
        $this->assertEquals('1234.56', $transaction->amount);
    }
}
