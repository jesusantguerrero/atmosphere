<?php

namespace App\Domains\Integration\Actions;

use Exception;
use Symfony\Component\DomCrawler\Crawler;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Automation\Concerns\AutomationActionContract;

class BHD implements AutomationActionContract
{
    const EMAIL_ALERT = 'alertas@bhd.com.do';
    const EMAIL_NOTIFICATION = 'notificaciones@bhd.com.do';

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
            null;
        }

    }

    public function getName(): string
    {
        return 'BHDMessage';
    }

    public function label(): string
    {
        return 'BHD Message';
    }

    public function getDescription(): string
    {
        return 'Parse an email alert or notification from BHD Leon Bank';
    }


    public static function parseAlert(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    ) {
        echo "\n Parsing alert \n\n";
        return (new BHDAlert())->handle($automation, $payload);
    }


    public static function parseNotification(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    ) {
        return (new BHDNotification())->handle($automation, $payload);
    }

    public static function getMessageType($mail)
    {
        try {
            $body = new Crawler($mail['message']);
            $tdValues = $body->filter('[class*=alert_div] td')->each(function (Crawler $node) {
                return $node->text();
            });

            return empty($tdValues) ? 'notification' : 'alert';
        } catch (Exception $e) {
            return 'notification';
        }
    }

    public static function getConditions() {
        return [[
                'conditionType' => GmailReceived::CONDITION_FROM,
                'value' => self::EMAIL_ALERT,
            ]
        ];
    }

    public static function parseCurrency($bhdCurrencyCode)
    {
        try {
            $BHD_CURRENCY_CODES = [
                'USD' => 'USD',
                'RD' => 'DOP',
            ];

            return $BHD_CURRENCY_CODES[$bhdCurrencyCode];
        } catch (Exception) {
            dd($bhdCurrencyCode, 'The code');
        }
    }

    public static function parseTypes($type)
    {
        $types = [
            'Compra' => 1,
            'compra' => 1,
            'retiro de efectivo' => 1,
            "reserva de fondos (hold)" => 1
        ];

        return $types[$type];
    }
}
