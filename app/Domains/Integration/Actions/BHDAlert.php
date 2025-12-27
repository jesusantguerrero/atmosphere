<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Concerns\MailToTransaction;
use App\Domains\Integration\Concerns\TransactionDataDTO;
use App\Domains\Transaction\Services\YNABService;
use Exception;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class BHDAlert implements MailToTransaction
{
    use BHDAction;

    public function handle(Automation $automation, mixed $mail, int $index = 0): TransactionDataDTO
    {
        try {
            $body = new Crawler($mail['message']);
            $product = $body->filter('[class*=titleA] + p')->first()->text();
            $tdValues = $body->filter('[class*=table_trans] tbody td')->each(function (Crawler $node) {
                return $node->text();
            });

            $total = YNABService::extractMoneyCurrency($tdValues[2]);
            $type = BHD::parseTypes(strtolower($tdValues[5])) ?? 1;

            $rawProduct = Str::of($product)->replace(' ', '')->explode('#');
            $productName = $rawProduct->get(0);
            $productCode = $rawProduct->get(1) ?? null;

            return new TransactionDataDTO([
                'id' => (int) $mail['id'],
                'messageId' => $mail['messageId'],
                'date' => date('Y-m-d', strtotime($mail['date'])),
                'payee' => $tdValues[3],
                'category' => '',
                'categoryGroup' => '',
                'description' => $product,
                'amount' => $total->amount * $type,
                'currencyCode' => BHD::parseCurrency($tdValues[1]),
                'productName' => $productName,
                'productCode' => $productCode,
                'productBrand' => 'BHD',
            ]);
        } catch (Exception $e) {
            dump($e->getMessage());
            throw $e;
        }
    }
}
