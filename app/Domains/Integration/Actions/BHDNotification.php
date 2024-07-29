<?php

namespace App\Domains\Integration\Actions;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use App\Domains\Automation\Models\Automation;
use App\Domains\Transaction\Services\YNABService;
use App\Domains\Integration\Concerns\MailToTransaction;
use App\Domains\Integration\Concerns\TransactionDataDTO;

class BHDNotification implements MailToTransaction
{
    use BHDAction;

    public function handle(Automation $automation, mixed $mail, int $index = 0): TransactionDataDTO | null
    {

        $body = new Crawler($mail['message']);
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
                echo $e->getMessage() . "\n\n";
                Log::error($e->getMessage(), [$e]);
                continue;
            }
        }

        print_r([$bhdOutput['amount']]);

        return new TransactionDataDTO([
            'id' => (int) $mail['id'],
            'date' => $bhdOutput['date'],
            'payee' => $bhdOutput['seller'],
            'category' => '',
            'categoryGroup' => '',
            'description' => $bhdOutput['product'].':'.$bhdOutput['description'],
            'amount' => $bhdOutput['amount']->amount * 1,
            'currencyCode' => $bhdOutput['amount']->currencyCode,
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
