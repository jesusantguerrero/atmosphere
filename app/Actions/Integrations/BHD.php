<?php

namespace App\Actions\Integrations;

use App\Models\Integrations\Automation;
use App\Models\User;
use App\Notifications\EntryGenerated;
use Insane\Journal\Account;
use Insane\Journal\Transaction;
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

        echo "BHD: " . json_encode($bhdOutput) . "\n";

        return $bhdOutput;
    }
}
