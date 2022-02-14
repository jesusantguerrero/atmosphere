<?php

namespace App\Actions\Integrations;

use App\Libraries\GoogleService;
use App\Models\Integrations\Automation;
use Google_Service_Gmail;
use Insane\Journal\Account;
use Insane\Journal\Transaction;
use Symfony\Component\DomCrawler\Crawler;
use PhpMimeMailParser\Parser as EmailParser;

class CreateEntry
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function fromEmail(Automation $automation, $mail, $config)
    {

        $tdValues = (new Crawler($mail['message']))->filter("[class*=table_trans_body] td")->each(function (Crawler $node, $i) {
            return $node->text();
        });
        $account = !empty($config->account_id) ? Account::find($config->account_id) : Account::defaultAccount($automation->user_id);
        $total = (int) $tdValues[2];
        print_r($tdValues);
        Transaction::createTransaction([
            'team_id' => $automation->team_id,
            'user_id' => $automation->user_id,
            'account_id' => $account->id,
            'date' => Date('Y-m-d', strtotime($mail['date'])),
            'category_id' => Account::guessAccount($automation, [$tdValues[3], $tdValues[5]]),
            'description' => $tdValues[3] . " - " . $tdValues[5],
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => $total,
            'items' => [],
            'metaData' => [
                "resource_id" => $mail['id'],
                "resource_origin" => 'message',
                "resource_type" => 'gmail',
            ]

        ]);
    }
}
