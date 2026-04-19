<?php

namespace App\Domains\Transaction\Services;

use App\Domains\Automation\Data\AutomationData;
use App\Domains\Automation\Enums\AutomationTaskType;
use App\Domains\Automation\Helpers\AutomationBuilder;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationService;
use App\Domains\Integration\Actions\GmailReceived;
use App\Domains\Integration\Actions\TransactionCreateEntry;
use App\Domains\Integration\Actions\UniversalBankParser;
use App\Domains\Transaction\Models\Transaction;
use App\Exceptions\UnsupportedBankException;
use Insane\Journal\Models\Core\Account;

class BankConnectionService
{
    public function linkAccountToBank(Account $account, string $bankCode, int $integrationId): Automation
    {
        $patterns = UniversalBankParser::getAllBankPatterns();
        if (! isset($patterns[$bankCode])) {
            throw new UnsupportedBankException($bankCode);
        }

        $service = AutomationService::firstOrCreate(
            ['name' => 'UniversalBankParser'],
            [
                'label' => 'Universal Bank Parser',
                'type' => 'internal',
                'entity' => UniversalBankParser::class,
                'handler' => UniversalBankParser::class,
                'description' => 'Automatically detects and parses transaction emails from multiple banks',
            ]
        );

        $emailAddresses = $patterns[$bankCode]['email_addresses'] ?? [];
        $query = 'from:('.implode(' OR ', $emailAddresses).')';

        $builder = AutomationBuilder::make(new AutomationData(
            $account->team_id,
            $account->user_id,
            $service->id,
            $integrationId
        ))
            ->addAction(new GmailReceived, AutomationTaskType::TRIGGER, [
                'values' => ['query' => $query],
            ])
            ->addAction(new UniversalBankParser, AutomationTaskType::COMPONENT, [])
            ->addAction(new TransactionCreateEntry, AutomationTaskType::ACTION, [
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

        $schema = $builder->getSchema();
        $automation = Automation::create([
            ...$schema,
            'automatable_id' => $account->id,
            'automatable_type' => get_class($account),
            'config' => [
                'bank_patterns' => [$bankCode => $patterns[$bankCode]],
            ],
        ]);
        $automation->saveTasks($schema['tasks']);

        $account->update(['bank_code' => $bankCode]);

        return $automation;
    }

    public function linkAccount(Account $account, AutomationService $automationService, $integrationId)
    {

        $handler = new $automationService->entity;

        $alertAutomation = AutomationBuilder::make(new AutomationData(
            $account->team_id,
            $account->user_id,
            $automationService->id,
            $integrationId

        ))
            ->addAction(new GmailReceived, AutomationTaskType::TRIGGER, [
                'values' => $handler->getConditions(),
            ])
            ->addAction($handler, AutomationTaskType::COMPONENT, [])
            ->addAction(new TransactionCreateEntry, AutomationTaskType::ACTION, [
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
