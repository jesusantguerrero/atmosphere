<?php

namespace App\Domains\Journal\Actions;

use Insane\Journal\Contracts\AccountDetailTypesCreates;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\AccountDetailType;

class AccountDetailTypesCreate implements AccountDetailTypesCreates
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  mixed  $user
     * @param  array  $input
     * @return mixed
     */
    public function create()
    {
        $detailTypes = [
            [
                'name' => AccountDetailType::BANK,
                'label' => 'Bank',
                'description' => 'Use Bank accounts to track all your current activity, including debit card transactions.
                Each current account your company has at a bank or other financial institution should have its own Bank type account in QuickBooks Online Simple Start.',
                'config' => [
                    'balance_type' => Account::BALANCE_TYPE_DEBIT,
                    'category_id' => 'cash_and_bank',
                ],
            ],
            [
                'name' => AccountDetailType::CASH,
                'label' => 'Cash and cash equivalents',
                'description' => 'Use Cash and Cash Equivalents to track cash or assets that can be converted into cash immediately. For example, marketable securities and Treasury bills.',
                'config' => [
                    'balance_type' => Account::BALANCE_TYPE_DEBIT,
                    'category_id' => 'cash_and_bank',
                ],
            ],
            [
                'name' => AccountDetailType::CASH_ON_HAND,
                'label' => 'Cash on hand',
                'description' => 'Use a Cash on hand account to track cash your company keeps for occasional expenses, also called petty cash.
                To track cash from sales that have not been deposited yet, use a pre-created account called Undeposited funds, instead.',
                'config' => [
                    'balance_type' => Account::BALANCE_TYPE_DEBIT,
                    'category_id' => 'cash_and_bank',
                ],
            ],
            [
                'name' => AccountDetailType::SAVINGS,
                'label' => 'Savings',
                'description' => 'Use Savings accounts to track your savings and CD activity.
                Each savings account your company has at a bank or other financial institution should have its own Savings type account.
                
                For investments, see Current Assets, instead.',
                'config' => [
                    'balance_type' => Account::BALANCE_TYPE_DEBIT,
                    'category_id' => 'cash_and_bank',
                ],
            ],
            [
                'name' => AccountDetailType::CREDIT_CARD,
                'label' => 'Credit Cards',
                'description' => 'Credit cards.',
                'config' => [
                    'balance_type' => Account::BALANCE_TYPE_CREDIT,
                    'category_id' => 'credit_card',
                ],
            ],
            [
                'name' => AccountDetailType::CLIENT_TRUST,
                'label' => 'Client trust account',
                'description' => 'Use a Cash on hand account to track cash your company keeps for occasional expenses, also called petty cash.
                To track cash from sales that have not been deposited yet, use a pre-created account called Undeposited funds, instead.',
                'config' => [],
            ],
            [
                'name' => AccountDetailType::MONEY_MARKET,
                'label' => 'Money market',
                'description' => 'Use Money market to track amounts in money market accounts.
                For investments, see Current Assets, instead.',
                'config' => [],
            ],
            [
                'name' => AccountDetailType::RENT_HELD_IN_TRUST,
                'label' => 'Rent held in trust',
                'description' => 'Use Rents held in trust to track deposits and rent held on behalf of the property owners.
                Typically only property managers use this type of account.',
                'config' => [],
            ],
        ];

        AccountDetailType::where('team_id', 0)->delete();
        foreach ($detailTypes as $detailType) {
            AccountDetailType::create(
                array_merge(
                    $detailType,
                    [
                        'config' => $detailType['config'],
                        'team_id' => 0,
                    ]

                ));
        }
    }
}
