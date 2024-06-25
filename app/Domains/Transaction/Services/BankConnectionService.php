<?php

namespace App\Domains\Transaction\Services;

use Exception;
use Insane\Journal\Models\Core\Account;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Data\AutomationData;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Integration\Actions\GmailReceived;
use App\Domains\Automation\Enums\AutomationTaskType;
use App\Domains\Automation\Models\AutomationService;
use App\Domains\Automation\Helpers\AutomationBuilder;
use App\Domains\Integration\Actions\TransactionCreateEntry;

class BankConnectionService
{
    public function linkAccount(Account $account, AutomationService $automationService, $integrationId)
    {

        $handler = new $automationService->entity;

        $alertAutomation = AutomationBuilder::make(new AutomationData(
            $account->team_id,
            $account->user_id,
            $automationService->id,
            $integrationId

        ))
            ->addAction(new GmailReceived(), AutomationTaskType::TRIGGER,   [
                'values' => $handler->getConditions()
            ])
            ->addAction($handler, AutomationTaskType::COMPONENT, [])
            ->addAction(new TransactionCreateEntry(), AutomationTaskType::ACTION, [
                'values' => [
                    'account_id' => $account->id,
                    'date' => '${date}',
                    'currency_code' => '${currencyCode}',
                    'category_id' => '',
                    'description' => '${description}',
                    'direction' => Transaction::DIRECTION_CREDIT,
                    'total' => '${amount}',
                    'items' => '',
                    'payee' => '${payee}',
                    'metaData' => '',
                ],
        ]);

        $automationDefinitions = [$alertAutomation->getSchema()];
        foreach ($automationDefinitions as $builderData) {
            $automation = Automation::create([
                ...$builderData,
                'automatable_id' => $account->id,
                'automatable_type' => get_class($account),
            ]);
            $automation->saveTasks($builderData['tasks']);
        }
    }
}
