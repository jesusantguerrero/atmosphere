<?php
namespace App\Domains\Transaction\Services;

use App\Domains\Automation\Data\AutomationData;
use App\Domains\Automation\Enums\AutomationTaskType;
use App\Domains\Automation\Helpers\AutomationBuilder;
use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Actions\BHD;
use App\Domains\Integration\Actions\GmailReceived;
use App\Domains\Integration\Actions\TransactionCreateEntry;
use App\Domains\Transaction\Models\Transaction;
use Exception;
use Insane\Journal\Models\Core\Account;

class BHDService {
    const CONDITION_FROM = "from";
    const CONDITION_TO = "to";
    const CONDITION_SUBJECT = "subject";
    const CONDITION_INCLUDES = "includes";
    const CONDITION_CUSTOM = "custom";
    const EMAIL_ALERT = "alertas@bhd.com.do";
    const EMAIL_NOTIFICATION = "notificaciones@bhd.com.do";

    public static function parseCurrency($bhdCurrencyCode) {
        try {
            $BHD_CURRENCY_CODES = [
                'USD' => 'USD',
                'RD' => 'DOP',
            ];

            return $BHD_CURRENCY_CODES[$bhdCurrencyCode];
        } catch (Exception) {
            dd($bhdCurrencyCode, "The code");
        }
    }

    public static function parseTypes($type) {
        $types = [
            'Compra' => 1,
            "compra" => 1,
            "retiro de efectivo" => 1
        ];

        return  $types[$type];
    }

    public function linkAccount(Account $account, $serviceId, $integrationId) {
        $alertAutomation = AutomationBuilder::make(new AutomationData(
            $account->team_id,
            $account->user_id,
            $serviceId,
            $integrationId

        ))
        ->addAction(new GmailReceived(), AutomationTaskType::TRIGGER, [
            "values" => [
                "conditionType" => BHDService::CONDITION_FROM,
                // "value" => implode(",", [BHDService::EMAIL_ALERT, BHDService::EMAIL_NOTIFICATION])
                "value" => BHDService::EMAIL_ALERT
            ]
        ])
        ->addAction(new BHD(), AutomationTaskType::COMPONENT, [])
        ->addAction(new TransactionCreateEntry(), AutomationTaskType::ACTION, [
            "values" => [
                'account_id' => $account->id,
                'date' => '${date}',
                'currency_code' => '${currencyCode}',
                'category_id' => '',
                'description' => '${description}',
                'direction' => Transaction::DIRECTION_CREDIT,
                'total' => '${amount}',
                'items' => '',
                'payee' => '${payee}',
                'metaData' => ''
            ]
        ]);

        // $notificationAutomation = AutomationBuilder::make(new AutomationData(
        //     $account->team_id,
        //     $account->user_id,
        //     $serviceId,
        //     $integrationId
        // ))
        // ->addAction(new GmailReceived(), AutomationTaskType::TRIGGER, [
        //     "values" => [
        //         "conditionType" => BHDService::CONDITION_FROM,
        //         "value" => BHDService::EMAIL_NOTIFICATION
        //     ]
        // ])
        // ->addAction(new BHD(), AutomationTaskType::COMPONENT, [])
        // ->addAction(new TransactionCreateEntry(), AutomationTaskType::ACTION, [
        //     "values" => [
        //         'account_id' => $account->id,
        //         'date' => '${date}',
        //         'currency_code' => '${currencyCode}',
        //         'category_id' => '',
        //         'description' => '${description}',
        //         'direction' => Transaction::DIRECTION_CREDIT,
        //         'total' => '${amount}',
        //         'items' => '',
        //         'payee' => '${payee}',
        //         'metaData' => ''
        //     ]
        // ]);

        $automationDefinitions = [$alertAutomation->getSchema(), ];
        foreach ($automationDefinitions as $builderData) {
            $automation = Automation::create([
                ...$builderData,
                "automatable_id" => $account->id,
                "automatable_type" => get_class($account)
            ]);
            $automation->saveTasks($builderData['tasks']);
        }

    }
}
