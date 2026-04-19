<?php

namespace App\Domains\Integration\Actions\Qik;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Integration\Actions\GmailReceived;
use Exception;
use Illuminate\Support\Facades\Log;

class Qik implements AutomationActionContract
{
    const EMAIL_ALERT = 'notificaciones@qik.do';

    const EMAIL_NOTIFICATION = 'notificaciones@qik.do';

    const EMAIL_NOTIFICATION_SUBJECT = 'Usaste tu tarjeta de crédito Qik';

    public static function handle(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    ) {
        try {
            $transaction = self::parseAlert($automation, $payload, $task, $previousTask, $trigger);

            return $transaction?->toArray();
        } catch (Exception $e) {
            Log::error($e->getMessage(), [$e, $payload['message'] ?? null]);

            return null;
        }
    }

    public function getName(): string
    {
        return 'QikMessage';
    }

    public function label(): string
    {
        return 'Qik Message';
    }

    public function getDescription(): string
    {
        return 'Parse an email alert or notification from Qik';
    }

    public static function parseAlert(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    ) {
        return (new QikAlert)->handle($automation, $payload);
    }

    public static function getConditions()
    {
        return [[
            'conditionType' => GmailReceived::CONDITION_FROM,
            'value' => self::EMAIL_ALERT,
        ], [
            'conditionType' => GmailReceived::CONDITION_SUBJECT,
            'value' => self::EMAIL_NOTIFICATION_SUBJECT,
        ]];
    }

    public static function parseCurrency($bankCurrencyCode)
    {
        $QIK_CURRENCY_CODES = [
            'RD$' => 'DOP',
            'USD$' => 'USD',
            'RD' => 'DOP',
            'USD' => 'USD',
        ];

        return $QIK_CURRENCY_CODES[$bankCurrencyCode] ?? 'DOP';
    }

    public static function parseTypes($type)
    {
        return 1;
    }

    /**
     * Get email patterns for bank detection in Universal Bank Parser.
     *
     * @return array Email patterns configuration
     */
    public static function getEmailPatterns(): array
    {
        return [
            'email_addresses' => [
                self::EMAIL_ALERT,
            ],
            'email_domains' => [
                '@qik.do',
            ],
            'subject_patterns' => [
                self::EMAIL_NOTIFICATION_SUBJECT,
            ],
        ];
    }
}
