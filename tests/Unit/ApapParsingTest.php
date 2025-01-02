<?php

namespace Tests\Unit;

use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class ApapParsingTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testShouldParseProductName()
    {
        $productLine = "Tu tarjeta de Crédito titular-Visa GoldAPAP terminada en 7106 presenta una transacción, con el siguiente detalle:";
        $visaPos = strpos($productLine, 'Visa');

        $product = Str::substr($productLine, $visaPos - 1, strpos($productLine, 'terminada') - $visaPos - 1);
        $this->assertEquals($product, "Visa GoldAPAP");
    }
}
