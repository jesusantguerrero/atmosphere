<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Concerns\MailToTransaction;
use App\Domains\Integration\Concerns\TransactionDataDTO;
use App\Domains\Transaction\Services\YNABService;
use App\Exceptions\TransactionInProgressException;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class BHDNotification implements MailToTransaction
{
    use BHDAction;

    public function handle(Automation $automation, mixed $mail, int $index = 0): ?TransactionDataDTO
    {
        $body = new Crawler($mail['message']);

        // Check if this is a completed transaction (has confirmation number)
        // In-progress transactions don't have idNumeroConfirmacion and will be followed by a confirmation email
        $confirmationElement = $body->filter('[id*=idNumeroConfirmacion]');
        if ($confirmationElement->count() === 0) {
            Log::info('BHD notification skipped - transaction in progress (no confirmation number)', [
                'email_id' => $mail['id'] ?? null,
                'subject' => $mail['subject'] ?? null,
            ]);

            throw new TransactionInProgressException('BHD transaction in progress - confirmation number not found');
        }

        $tableIds = [
            'idProductoOrigen' => [
                'processor' => 'processResult',
                'name' => 'product',
            ],
            'idProductoDestino' => [
                'processor' => 'processResult',
                'name' => 'seller',
            ],
            'idDescripcion' => [
                'processor' => 'processResult',
                'name' => 'description',
            ],

            'idMonto' => [
                'processor' => 'processMoney',
                'name' => 'amount',
            ],
            'idBeneficiario' => [
                'processor' => 'processResult',
                'name' => 'seller',
            ],
            'idNumeroConfirmacion' => [
                'processor' => 'processResult',
                'name' => 'confirmation_number',
            ],
            'idFechayHoraTransaccion' => [
                'processor' => 'processDate',
                'name' => 'date',
            ],
            'idTipoTransaccion' => [
                'processor' => 'processType',
                'name' => 'type',
            ],
        ];
        foreach ($tableIds as $fieldName => $field) {
            try {
                $bhdOutput[$field['name']] = $this->{$field['processor']}($body->filter("[id*=$fieldName]")->first()->text());
            } catch (Exception $e) {
                if (in_array($fieldName, ['idBeneficiario', 'idProductoDestino'])) {
                    continue;
                }
                $message = $e->getMessage().'( '.$field['name'].' '.$fieldName.")\n\n";
                echo $mail['message']."\n\n";
                Log::error($message, [$e]);

                continue;
            }
        }

        $product = str_replace('X', '', $bhdOutput['product']);
        $seller = str_replace('X', '', $bhdOutput['seller'] ?? '');

        // get the last 4 digits of the product
        $productLast4 = substr($product, -4);

        return new TransactionDataDTO([
            'id' => (int) $mail['id'],
            'messageId' => $mail['messageId'],
            'date' => $bhdOutput['date'],
            'payee' => $seller ?? $bhdOutput['seller'] ?? 'BHD Notification',
            'category' => '',
            'categoryGroup' => '',
            'description' => $bhdOutput['product'].':'.$bhdOutput['description'],
            'amount' => $bhdOutput['amount']->amount * 1,
            'currencyCode' => $bhdOutput['amount']->currencyCode,
            'productName' => $product,
            'productCode' => $productLast4,
            'productBrand' => 'BHD',
        ]);
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
        $date = substr($value, 0, 10);

        return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
    }

    public function processType($value)
    {
        return strtolower($value);
    }
}
