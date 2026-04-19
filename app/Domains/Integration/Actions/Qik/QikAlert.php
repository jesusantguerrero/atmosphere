<?php

namespace App\Domains\Integration\Actions\Qik;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Concerns\MailToTransaction;
use App\Domains\Integration\Concerns\TransactionDataDTO;
use DateTime;
use Symfony\Component\DomCrawler\Crawler;

class QikAlert implements MailToTransaction
{
    use QikAction;

    public function handle(Automation $automation, mixed $mail, int $index = 0): ?TransactionDataDTO
    {
        $html = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $mail['message']);
        $html = str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">', '', $html);

        $body = new Crawler($html, true, null, true);
        if (! $body) {
            return null;
        }

        $text = $body->text();
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/[\s\n\r]+/', ' ', $text);
        $text = trim($text);

        preg_match('/transacci[óo]n de\s*(RD\$|USD\$)\s*([\d,]+\.?\d*)/iu', $text, $amountMatches);
        $currencyCode = isset($amountMatches[1]) ? Qik::parseCurrency($amountMatches[1]) : 'DOP';
        $total = isset($amountMatches[2]) ? (int) str_replace(',', '', $amountMatches[2]) : 0;

        preg_match('/Localidad\s+(.+?)\s+Fecha y hora/iu', $text, $payeeMatches);
        $payee = isset($payeeMatches[1]) ? trim($payeeMatches[1]) : '';

        preg_match('/Fecha y hora\s+(\d{2}-\d{2}-\d{4})/iu', $text, $dateMatches);
        $parsedDate = isset($dateMatches[1]) ? DateTime::createFromFormat('m-d-Y', $dateMatches[1]) : false;
        $date = $parsedDate ? $parsedDate->format('Y-m-d') : date('Y-m-d', strtotime($mail['date']));

        preg_match('/termina en\s+[\d\*]*?(\d{4})\b/iu', $text, $cardMatches);
        $productCode = isset($cardMatches[1]) ? $cardMatches[1] : '';

        preg_match('/tarjeta\s+(cr[ée]dito|d[ée]bito)\s+Qik/iu', $text, $productMatches);
        $productType = isset($productMatches[1]) ? trim($productMatches[1]) : '';
        $product = $productType ? 'Tarjeta '.$productType.' Qik' : 'Qik';

        $type = 1;

        return new TransactionDataDTO([
            'id' => (int) $mail['id'],
            'date' => $date,
            'payee' => $payee,
            'category' => '',
            'categoryGroup' => '',
            'description' => $product,
            'productCode' => $productCode,
            'amount' => $total * $type,
            'currencyCode' => $currencyCode,
            'productName' => $product,
            'productBrand' => 'Qik',
        ]);
    }
}
