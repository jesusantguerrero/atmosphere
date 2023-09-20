<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Insane\Journal\Models\Core\Account;

class AccountApiController extends Controller
{
    public function bulkUpdate(Request $request)
    {
        $accounts = $request->post('accounts');
        Account::whereIn('id', array_keys($accounts))->chunkById(100, function ($savedAccounts) use ($accounts) {
            foreach ($savedAccounts as $account) {
                $account->update($accounts[$account->id]);
            }
        });

        return response()->json(['success' => true]);
    }
}
