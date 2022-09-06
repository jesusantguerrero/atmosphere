<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Integration\Concerns\MailToTransaction;
use App\Domains\Integration\Concerns\TransactionDataDTO;
use App\Domains\Integration\Models\Automation;
use App\Domains\Transaction\Services\BHDService;
use App\Domains\Transaction\Services\YNABService;
use Symfony\Component\DomCrawler\Crawler;

class BHDNotification implements MailToTransaction
{
    public function handle(Automation $automation, mixed $mail, int $index): TransactionDataDTO
    {

        $body = new Crawler($mail['message']);
        $tableIds = [
            "idProductoOrigen" => [
                'processor' => "processResult",
                'name' => 'product',
            ],
            "idProductoDestino" => [
                'processor' => "processResult",
                'name' => 'seller',
            ],
            "idDescripcion" => [
                'processor' => "processResult",
                'name' => 'description',
            ],

            "idMonto" => [
                'processor' => "processMoney",
                'name' => 'amount',
            ],
            "idBeneficiario" => [
                'processor' => "processResult",
                'name' => 'seller',
            ],
            "idNumeroConfirmacion" => [
                'processor' => "processResult",
                'name' => 'confirmation_number',
            ],
            "idFechayHoraTransaccion" => [
                'processor' => "processDate",
                'name' => 'date',
            ],
            "idTipoTransaccion" => [
                'processor' => "processType",
                'name' => 'type',
            ]
        ];

        foreach ($tableIds as $fieldName => $field) {
            $bhdOutput[$field['name']] = $this->{$field['processor']}($body->filter("[id*=${fieldName}")->first()->text());
        }
        return new TransactionDataDTO([
                "id" => $mail['id'],
                "date" => $bhdOutput['date'],
                "payee" => $bhdOutput['seller'],
                'category' => '',
                'categoryGroup' => '',
                'description' => $bhdOutput['product'],
                "amount" => $bhdOutput['amount']->amount * 1,
                "currencyCode" => $bhdOutput['amount']->currencyCode,
        ]);
    }

    public static function getSchema(): mixed
    {
        return [
            'description'=> [
                'type' => 'string',
                'required' => true
            ],
            'date' => [
                'type' => 'date',
                'label' => 'Date',

            ],
            'currencyCode' => [
                'type' => 'string',
                'label' => 'currencyCode',
                'required' => true
            ],
            'amount' => [
                'type' => 'money',
                'label' => 'Amount',
                'required' => true
            ],
            'payee' => [
                'type' => 'string',
                'label' => 'Payee',
                'required' => true
            ],
            'categoryGroup' => [
                'type' => 'string',
                'label' => 'categoryGroup',
                'required' => true
            ],
            'category' => [
                'type' => 'string',
                'label' => 'Category',
                'required' => true
            ]
        ];
    }


    public function processResult($value)
    {
        return $value;
    }

    public function processMoney($value)
    {
        return YNABService::extractMoneyCurrency($value);
    }

    public function processDate($value)
    {
        return Date('Y-m-d', strtotime($value));
    }

    public function processType($value)
    {
        return strtolower($value);
    }
}
