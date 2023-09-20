<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Concerns\MailToTransaction;
use App\Domains\Integration\Concerns\TransactionDataDTO;
use App\Domains\Transaction\Services\BHDService;
use Symfony\Component\DomCrawler\Crawler;

class BHDAlert implements MailToTransaction
{
    use BHDAction;

    public function handle(Automation $automation, mixed $mail, int $index = 0): TransactionDataDTO
    {

        $body = new Crawler($mail['message']);
        $product = $body->filter('[class*=titleA] + p')->first()->text();
        $tdValues = $body->filter('[class*=table_trans_body] td')->each(function (Crawler $node) {
            return $node->text();
        });

        $total = (int) str_replace(',', '', $tdValues[2]);
        $type = BHDService::parseTypes(strtolower($tdValues[5])) ?? 1;

        return new TransactionDataDTO([
            'id' => (int) $mail['id'],
            'date' => date('Y-m-d', strtotime($mail['date'])),
            'payee' => $tdValues[3],
            'category' => '',
            'categoryGroup' => '',
            'description' => $product,
            'amount' => $total * $type,
            'currencyCode' => BHDService::parseCurrency($tdValues[1]),
        ]);
    }
}
