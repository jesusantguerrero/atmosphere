<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Integration\Models\Automation;
use Symfony\Component\DomCrawler\Crawler;

class BHD
{
    const BHD_CURRENCY_CODES = [
        'USD' => 'USD',
        'RD' => 'DOP',
    ];
    /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function parseAlert(Automation $automation, $mail, $index)
    {

        $body = new Crawler($mail['message']);
        $product = $body->filter("[class*=titleA] + p")->first()->text();
        $tdValues = $body->filter("[class*=table_trans_body] td")->each(function (Crawler $node, $i) {
            return $node->text();
        });
        $total = (int) str_replace(',','', $tdValues[2]);

        $bhdOutput = [
            "id" => $mail['id'],
            "date" => Date('Y-m-d', strtotime($mail['date'])),
            "currency_code" => self::BHD_CURRENCY_CODES[$tdValues[1]],
            "amount" => $total,
            "seller" => $tdValues[3],
            "status" => strtolower($tdValues[4]),
            "type" => strtolower($tdValues[5]),
            'product' => $product,
        ];

        return $bhdOutput;
    }

    public static function parseNotification(Automation $automation, $mail, $index)
    {

        $processResult = self::processResult;
        $processMoney = self::processMoney;
        $processDate = self::processDate;
        $processType = self::processType;

        $body = new Crawler($mail['message']);
        $tableIds = [
            "idProductoOrigen" => [
                'processor' => $processResult,
                'name' => 'product',
            ],
            "idProductoDestino" => [
                'processor' => $processResult,
                'name' => 'seller',
            ],
            "idDescripcion" => [
                'processor' => $processResult,
                'name' => 'description',
            ],

            "idMonto" => [
                'processor' => $processMoney,
                'name' => 'amount',
            ],
            "idBeneficiario" => [
                'processor' => $processResult,
                'name' => 'seller',
            ],
            "idNumeroConfirmacion" => [
                'processor' => $processResult,
                'name' => 'confirmation_number',
            ],
            "idFechayHoraTransaccion" => [
                'processor' => $processDate,
                'name' => 'date',
            ],
            "idTipoTransaccion" => [
                'processor' => $processType,
                'name' => 'type',
            ]
        ];

        $bhdOutput = [
            "id" => $mail['id']
        ];
        foreach ($tableIds as $fieldName => $field) {
            $bhdOutput[$field['name']] = $field->processor($body->filter("[id*=${fieldName}")->first()->text());
        }
        return $bhdOutput;
    }

    public static function processResult($value)
    {
        return $value;
    }

    public static function processMoney($value)
    {
        return (int) str_replace(',','', $value);
    }

    public static function processDate($value)
    {
        return Date('Y-m-d', strtotime($value));
    }

    public static function processType($value)
    {
        return strtolower($value);
    }
}
