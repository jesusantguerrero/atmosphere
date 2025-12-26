<?php

namespace App\Domains\Integration\Actions\APAP;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Integration\Actions\GmailReceived;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class APAP implements AutomationActionContract
{
    const EMAIL_ALERT = 'NO-REPLY@apap.com.do';

    const EMAIL_NOTIFICATION = 'NO-REPLY@apap.com.do';

    const EMAIL_NOTIFICATION_SUBJECT = 'APAP, Notificaciones';

    const EMAIL_BCRD_SUBJECT = 'APAP, Pago Al Instante BCRD';

    public static function handle(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    ) {
        $type = self::getMessageType($payload);
        try {
            $transaction = match ($type) {
                'alert' => self::parseAlert($automation, $payload, $task, $previousTask, $trigger),
                'notification' => self::parseNotification($automation, $payload, $task, $previousTask, $trigger),
            };

            return $transaction?->toArray();
        } catch (Exception $e) {
            dd('hola', $e);
        }

    }

    public function getName(): string
    {
        return 'APAPMessage';
    }

    public function label(): string
    {
        return 'APAP Message';
    }

    public function getDescription(): string
    {
        return 'Parse an email alert or notification from Asociacion Popular de Ahorros y Prestamos';
    }

    /**
     * Validate and create a new team for the given user.
     *
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function parseAlert(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    ) {

        return (new APAPAlert)->handle($automation, $payload);
    }

    /**
     * Validate and create a new team for the given user.
     *
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function parseNotification(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    ) {

        return (new APAPAlert)->handle($automation, $payload);
    }

    public static function getMessageType($mail)
    {
        try {
            $body = new Crawler($mail['message']);
            $tdValues = $body->filter('[class*=table_trans_body] td')->each(function (Crawler $node) {
                return $node->text();
            });

            return empty($tdValues) ? 'notification' : 'alert';
        } catch (Exception) {
            return 'notification';
        }
    }

    public static function getConditions()
    {
        return [[
            'conditionType' => GmailReceived::CONDITION_FROM,
            'value' => self::EMAIL_ALERT,
        ], [
            'conditionType' => GmailReceived::CONDITION_SUBJECT,
            'value' => self::EMAIL_NOTIFICATION_SUBJECT,
        ],
        ];
    }

    public static function parseCurrency($nankCurrencyCode)
    {
        try {
            $APAP_CURRENCY_CODES = [
                'USD dólar estadounidense' => 'USD',
                'RD' => 'DOP',
                'RD pesos dominicanos' => 'DOP',
                'USD dólar estadounidense' => 'USD',
            ];

            return $APAP_CURRENCY_CODES[$nankCurrencyCode];
        } catch (Exception $e) {
            Log::error($e->getMessage(), [$nankCurrencyCode]);
        }
    }

    public static function parseTypes($type)
    {
        return 1;
    }
}
