<?php
namespace App\Domains\Transaction\Services;

use App\Domains\Automation\Data\AutomationData;
use App\Domains\Automation\Enums\AutomationTaskType;
use App\Domains\Automation\Helpers\AutomationBuilder;
use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Actions\BHD;
use App\Domains\Integration\Actions\GmailReceived;
use App\Domains\Integration\Actions\TransactionCreateEntry;
use Insane\Journal\Models\Core\Account;

class BHDService {
    const CONDITION_FROM = "from";
    const CONDITION_TO = "to";
    const CONDITION_SUBJECT = "subject";
    const CONDITION_INCLUDES = "includes";
    const CONDITION_CUSTOM = "custom";
    const EMAIL_ALERT = "alert.bhd.do";

    public static function parseCurrency($bhdCurrencyCode) {
        $BHD_CURRENCY_CODES = [
            'USD' => 'USD',
            'RD' => 'DOP',
        ];
        return $BHD_CURRENCY_CODES[$bhdCurrencyCode];
    }

    public static function parseTypes($type) {
        $types = [
            'Compra' => 1
        ];

        return  $types[$type];
    }

    public function linkAccount(Account $account, $serviceId, $integrationId) {
        $builder = AutomationBuilder::make(new AutomationData(
            $account->team_id,
            $account->user_id,
            $serviceId,
            $integrationId

        ));

        $builder
        ->addAction(new GmailReceived(), AutomationTaskType::TRIGGER, [
            "conditionType" => BHDService::CONDITION_FROM,
            "value" => BHDService::EMAIL_ALERT
        ])
        ->addAction(new BHD(), AutomationTaskType::COMPONENT, [])
        ->addAction(new TransactionCreateEntry(), AutomationTaskType::ACTION, [
            'account_id' => $account->id
        ]);

        $builderData = $builder->getSchema();
        // dd($builderData);
        $automation = Automation::create($builderData);
        $automation->saveTasks($builderData['tasks']);
    }
}
