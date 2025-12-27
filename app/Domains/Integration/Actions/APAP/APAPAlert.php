<?php

namespace App\Domains\Integration\Actions\APAP;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Concerns\MailToTransaction;
use App\Domains\Integration\Concerns\TransactionDataDTO;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class APAPAlert implements MailToTransaction
{
    use APAPAction;

    public function handle(Automation $automation, mixed $mail, int $index = 0): ?TransactionDataDTO
    {
        $html = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $mail['message']);
        $html = str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">', '', $html);
        // dd($html);
        $body = new Crawler($html, true, null, true);
        // try {
        if (! $body) {
            return null;
        }
        $productLine = $body->filter('table p:nth-child(2)')->first()->text();
        // the product line is like this: "Tu tarjeta de Crédito titular-Visa Gold APAP terminada en 7106 presenta\n                                        una transacción, con el siguiente detalle:"
        // we need to extract the product name between "titular-" and "terminada"
        // and the number after "terminada en"

        // Clean up encoding issues, non-breaking spaces, and newlines
        $productLine = str_replace("\xc2\xa0", ' ', $productLine); // Replace non-breaking space
        $productLine = preg_replace('/[\s\n\r]+/', ' ', $productLine); // Replace multiple whitespace/newlines with single space
        $productLine = trim($productLine);

        // Use regex to extract product name between "titular-" and "terminada"
        preg_match('/titular-(.*?)\s+terminada/', $productLine, $matches);
        $product = isset($matches[1]) ? trim($matches[1]) : '';

        // Extract product code after "terminada en"
        preg_match('/terminada en\s+(\d+)/', $productLine, $codeMatches);
        $productCode = isset($codeMatches[1]) ? $codeMatches[1] : '';

        $tdValues = $body->filter('table td')->each(function (Crawler $node) {
            return $node->text();
        });

        $total = (int) str_replace(',', '', $tdValues[13]);
        $type = 1;

        return new TransactionDataDTO([
            'id' => (int) $mail['id'],
            'date' => date('Y-m-d', strtotime($mail['date'])),
            'payee' => $tdValues[15],
            'category' => '',
            'categoryGroup' => '',
            'description' => $product,
            'productCode' => $productCode,
            'productName' => $product,
            'amount' => $total * $type,
            'currencyCode' => APAP::parseCurrency($tdValues[11]) ?? 'DOP',
            'productBrand' => 'APAP',
        ]);
        // } catch (Exception $e) {
        //     Log::error($e->getMessage(), [$e]);
        //     return null;
        // }

    }
}
