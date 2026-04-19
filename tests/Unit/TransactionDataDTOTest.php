<?php

namespace Tests\Unit;

use App\Domains\Integration\Concerns\TransactionDataDTO;
use Tests\TestCase;

class TransactionDataDTOTest extends TestCase
{
    private function make(array $overrides = []): TransactionDataDTO
    {
        return new TransactionDataDTO(array_merge([
            'id' => 1,
            'date' => '2026-04-14',
            'payee' => 'HELADOS BON',
            'description' => 'Tarjeta crédito Qik',
            'categoryGroup' => '',
            'category' => '',
            'amount' => 100,
            'currencyCode' => 'DOP',
        ], $overrides));
    }

    /** @test */
    public function it_leaves_plain_merchant_names_untouched()
    {
        $dto = $this->make(['payee' => 'HELADOS BON MPZA LA RO']);

        $this->assertEquals('HELADOS BON MPZA LA RO', $dto->payee);
    }

    /** @test */
    public function it_strips_a_bare_full_card_number_from_payee()
    {
        $dto = $this->make(['payee' => 'JOHN DOE 4532123456789012']);

        $this->assertEquals('JOHN DOE', $dto->payee);
    }

    /** @test */
    public function it_strips_a_space_separated_card_number_from_payee()
    {
        $dto = $this->make(['payee' => 'JOHN DOE 4532 1234 5678 9012']);

        $this->assertEquals('JOHN DOE', $dto->payee);
    }

    /** @test */
    public function it_strips_a_dash_separated_card_number_from_payee()
    {
        $dto = $this->make(['payee' => 'JOHN DOE 4532-1234-5678-9012']);

        $this->assertEquals('JOHN DOE', $dto->payee);
    }

    /** @test */
    public function it_leaves_masked_card_numbers_untouched()
    {
        $dto = $this->make(['payee' => 'Card 53*************3861']);

        $this->assertEquals('Card 53*************3861', $dto->payee);
    }

    /** @test */
    public function it_leaves_short_numbers_like_phone_numbers_untouched()
    {
        $dto = $this->make(['payee' => 'Call 8093642161']);

        $this->assertEquals('Call 8093642161', $dto->payee);
    }

    /** @test */
    public function it_strips_card_number_from_description()
    {
        $dto = $this->make(['description' => 'Visa Gold 4532123456789012']);

        $this->assertEquals('Visa Gold', $dto->description);
    }

    /** @test */
    public function it_handles_null_and_empty_values()
    {
        $dto = $this->make(['payee' => '', 'description' => '']);

        $this->assertEquals('', $dto->payee);
        $this->assertEquals('', $dto->description);
    }
}
