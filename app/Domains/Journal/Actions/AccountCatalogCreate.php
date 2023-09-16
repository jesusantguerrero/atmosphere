<?php

namespace App\Domains\Journal\Actions;

use App\Models\Team;
use Insane\Journal\Contracts\AccountCatalogCreates;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Category;

class AccountCatalogCreate implements AccountCatalogCreates
{
    /**
     * Creates a new accounts catalog for the given team.
     */
    public function createCatalog($team)
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

    public function createChart(Team $team)
    {
        Category::where([
            'team_id' =>  0,
            'resource_type' => 'accounts'
        ])->delete();

        $categories = config('journal.accounts_categories');
        $generalInfo = [
            'team_id' => $team->id,
            'user_id' => $team->user_id,
            'depth' => 0,
            'resource_type' => 'accounts'
        ];

        Category::saveBulk($categories, $generalInfo);
    }
}
