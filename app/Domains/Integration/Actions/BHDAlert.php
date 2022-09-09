<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Integration\Concerns\MailToTransaction;
use App\Domains\Integration\Concerns\TransactionDataDTO;
use App\Domains\Integration\Models\Automation;
use App\Domains\Transaction\Services\BHDService;
use Symfony\Component\DomCrawler\Crawler;

class BHDAlert implements MailToTransaction
{
    public function handle(Automation $automation, mixed $mail, int $index): TransactionDataDTO
    {

        $body = new Crawler($mail['message']);
        $product = $body->filter("[class*=titleA] + p")->first()->text();
        $tdValues = $body->filter("[class*=table_trans_body] td")->each(function (Crawler $node) {
            return $node->text();
        });

        $total = (int) str_replace(',','', $tdValues[2]);
        $type = BHDService::parseTypes(strtolower($tdValues[5])) ?? 1;


        return new TransactionDataDTO([
            "id" => $mail['id'],
            "date" => Date('Y-m-d', strtotime($mail['date'])),
            "payee" => $tdValues[3],
            'category' => '',
            'categoryGroup' => '',
            'description' => $product,
            "amount" => $total * $type,
            "currencyCode" => BHDService::parseCurrency([$tdValues[1]]),
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

}
