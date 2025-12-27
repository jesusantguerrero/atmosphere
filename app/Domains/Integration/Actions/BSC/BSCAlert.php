<?php

namespace App\Domains\Integration\Actions\BSC;

use Symfony\Component\DomCrawler\Crawler;
use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Concerns\MailToTransaction;
use App\Domains\Integration\Concerns\TransactionDataDTO;

class BSCAlert implements MailToTransaction
{
    use BSCAction;

    public function handle(Automation $automation, mixed $mail, int $index = 0): TransactionDataDTO | null
    {
        $html= str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $mail['message']);
        $html= str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">', "", $html);
        // dd($html);
        $body = new Crawler($html, true, null , true);
        // try {
            if (!$body) return null;
            $productLine = $body->filter('table p')->first()->text();
            // the product line is like this: "Tu tarjeta de Crédito titular-Visa Gold APAP terminada en 7106 presenta\n                                        una transacción, con el siguiente detalle:"
            // we need to extract the product name between "titular-" and "terminada"
            // and the number after "terminada en"

            // Clean up encoding issues, non-breaking spaces, and newlines
            $productLine = str_replace("\xc2\xa0", ' ', $productLine); // Replace non-breaking space
            $productLine = preg_replace('/[\s\n\r]+/', ' ', $productLine); // Replace multiple whitespace/newlines with single space
            $productLine = trim($productLine);

            // Extract product name: "tarjeta de Crédito {ProductName} terminada en"
            preg_match('/tarjeta de Crédito\s+(.*?)\s+terminada/', $productLine, $matches);
            $product = isset($matches[1]) ? trim($matches[1]) : '';

            // Extract product code after "terminada en"
            preg_match('/terminada en\s+(\d+)/', $productLine, $codeMatches);
            $productCode = isset($codeMatches[1]) ? $codeMatches[1] : '';

            // Extract amount: "Monto: RD$ 4,115.00"
            preg_match('/Monto:\s*(RD\$|USD\$)?\s*([\d,]+\.?\d*)/', $productLine, $amountMatches);
            $total = isset($amountMatches[2]) ? (int) str_replace(',', '', $amountMatches[2]) : 0;
            $currencyCode = isset($amountMatches[1]) ? str_replace('$', '', $amountMatches[1]) : 'RD$';

            // Extract payee/location: "Lugar de transacción: {location}"
            preg_match('/Lugar de transacción:\s*(.*?)(?=Fecha y hora|$)/', $productLine, $payeeMatches);
            $seller = isset($payeeMatches[1]) ? trim($payeeMatches[1]) : '';

            // Extract date and time: "Fecha y hora: 17/12/2025  20:13:47"
            preg_match('/Fecha y hora:\s*(\d{1,2}\/\d{1,2}\/\d{4})\s+/', $productLine, $dateMatches);
            $date = isset($dateMatches[1]) ? date('Y-m-d', strtotime(str_replace('/', '-', $dateMatches[1]))) : date('Y-m-d', strtotime($mail['date']));

            // Extract status: "Estado: Aprobada"
            preg_match('/Estado:\s*(.*?)(?:\n|$)/', $productLine, $statusMatches);
            $status = isset($statusMatches[1]) ? trim($statusMatches[1]) : '';

            $type = 1;

            dump($product, $productCode, $total, $seller, $date, $status, $currencyCode);

            return new TransactionDataDTO([
                'id' => (int) $mail['id'],
                'date' => $date,
                'payee' => $seller,
                'category' => '',
                'categoryGroup' => '',
                'description' => $product,
                'productCode' => $productCode,
                'amount' => $total * $type,
                'currencyCode' => $currencyCode,
            ]);
        // } catch (Exception $e) {
        //     Log::error($e->getMessage(), [$e]);
        //     return null;
        // }

    }
}
