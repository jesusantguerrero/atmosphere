<?php

namespace App\Domains\Integration\Actions\APAP;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Concerns\MailToTransaction;
use App\Domains\Integration\Concerns\TransactionDataDTO;

class APAPAlert implements MailToTransaction
{
    use APAPAction;

    public function handle(Automation $automation, mixed $mail, int $index = 0): TransactionDataDTO | null
    {
        $html= str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $mail['message']);
        $html= str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">', "", $html);
        // dd($html);
        $body = new Crawler($html, true, null , true);
        try {
            if (!$body) return null;
            $productLine = $body->filter('table')->first()->text();
            $visaPos = strpos($productLine, 'Visa');
            $product = Str::substr($productLine, $visaPos - 1, strpos($productLine, 'terminada') - $visaPos - 1);


            $tdValues = $body->filter('table td')->each(function (Crawler $node) {
                return $node->text();
            });

            $total = (int) str_replace(',', '', $tdValues[13]);
            $type =  1;

            return new TransactionDataDTO([
                'id' => (int) $mail['id'],
                'date' => date('Y-m-d', strtotime($mail['date'])),
                'payee' => $tdValues[15],
                'category' => '',
                'categoryGroup' => '',
                'description' => $product,
                'amount' => $total * $type,
                'currencyCode' => APAP::parseCurrency($tdValues[11]),
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }

    }
}
