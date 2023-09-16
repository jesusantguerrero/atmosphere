<?php

namespace App\Domains\Journal\Actions;

use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\AccountDetailType;

class AccountCatalogCreate
{
    /**
     * Creates a new accounts catalog for the given team.
     */
    public function create($team)
    {

        $generalInfo = [
            'team_id' => $team->id,
            'user_id' => $team->user_id,
        ];

        Account::where('team_id', $team->id)->delete();
        $accounts = config('journal.accounts_catalog');

        if (count($accounts)) {
            foreach ($accounts as $index => $account) {
                $detailType = isset($account['detail_type']) ? $account['detail_type'] : 'bank';
                $type = $account['balance_type'] == 'DEBIT' ? 1 : -1;
                Account::create(array_merge($account, $generalInfo, [
                    'index' => $index,
                    'type' => $type,
                    'account_detail_type_id' => AccountDetailType::where('name', $detailType)->first()->id
                ]));
            }
        }
    }
}
