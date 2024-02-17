<?php

namespace App\Models;

use Laravel\Paddle\Billable;
use App\Domains\Meal\Models\Meal;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Payee;
use Insane\Journal\Models\Core\Account;
use App\Domains\AppCore\Models\Category;
use Spatie\Onboard\Concerns\Onboardable;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Spatie\Onboard\Concerns\GetsOnboarded;
use Laravel\Jetstream\Team as JetstreamTeam;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends JetstreamTeam implements Onboardable
{
    use Billable;
    use GetsOnboarded;
    use HasFactory;

    protected $with = ['settings'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'personal_team' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function timezone()
    {
        return $this->hasOne(Setting::class)->where(['name' => 'team_timezone']);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function budgetAccounts()
    {
        return $this->hasMany(Account::class)->whereIn('account_detail_type_id', [1, 2, 4, 5]);
    }

    public function budgetCategories()
    {
        return $this->hasMany(Category::class)
            ->where('resource_type', 'transactions')
            ->whereNotNull('parent_id');
    }

    public function categoryGroups()
    {
        return $this->hasMany(Category::class)->where([
            'resource_type' => 'transactions',
        ])->whereNull('parent_id');
    }

    public function payees()
    {
        return $this->hasMany(Payee::class);
    }

    public function meals()
    {
        return $this->hasMany(Meal::class);
    }

    /**
     * Get account balance.
     *
     * @return string
     */
    public function balance($upToDate = null, $accountIds = [])
    {
        return (float) Transaction::byTeam($this->id)
            ->verified()
            ->balance($upToDate, $accountIds)
            ->first()
        ->total;
    }

    public function balanceDetail($upToDate = null, $accountIds = [])
    {

        return DB::table('transaction_lines')->where([
            'transactions.status' => Transaction::STATUS_VERIFIED,
            'transactions.team_id' => $this->id,
        ])
        ->when($upToDate, fn($q) => $q->whereRaw('transaction_lines.date <= ?', [$upToDate]))
        ->when(count($accountIds), fn($q) => $q->whereIn('transaction_lines.account_id', $accountIds))
        ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
        ->join('accounts', 'accounts.id', 'transaction_lines.account_id')
        ->selectRaw("sum(amount * transaction_lines.type) as balance, transaction_lines.account_id, accounts.name")
        ->groupBy('accounts.id')
        ->get();
    }
}
