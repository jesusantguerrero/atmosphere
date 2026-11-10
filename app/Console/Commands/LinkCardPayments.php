<?php

namespace App\Console\Commands;

use App\Domains\Transaction\Services\CreditCardPaymentDetector;
use Illuminate\Console\Command;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;

class LinkCardPayments extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:link-card-payments {--days=120 : How far back to look} {--apply : Save the changes (without it, only lists them)}';

    /**
     * @var string
     */
    protected $description = 'Turn card payments saved as plain expenses («Pago TC Qik 3861») into transfers to the card';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $found = 0;

        Transaction::query()
            ->where('direction', Transaction::DIRECTION_CREDIT)
            ->where('status', Transaction::STATUS_VERIFIED)
            ->where(fn ($query) => $query->whereNull('is_transfer')->orWhere('is_transfer', false))
            ->where('date', '>=', now()->subDays((int) $this->option('days'))->format('Y-m-d'))
            ->orderBy('date')
            ->each(function (Transaction $transaction) use ($apply, &$found) {
                $counter = $transaction->counter_account_id ? Account::find($transaction->counter_account_id) : null;
                if ($counter && CreditCardPaymentDetector::isCreditCard($counter)) {
                    return;
                }

                $card = CreditCardPaymentDetector::cardFor((int) $transaction->team_id, $transaction->description, $transaction->account_id);
                if (! $card) {
                    return;
                }

                $found++;
                $this->line("#{$transaction->id} {$transaction->date} {$transaction->total} «{$transaction->description}» → {$card->name}");

                if ($apply) {
                    $transaction->updateTransaction([
                        ...$transaction->only(['team_id', 'user_id', 'account_id', 'date', 'description', 'direction', 'total', 'currency_code', 'status', 'reference', 'meta_data']),
                        'counter_account_id' => $card->id,
                        'is_transfer' => true,
                        'payee_id' => null,
                        'category_id' => null,
                    ]);
                }
            });

        $this->info($apply ? "{$found} card payments linked." : "{$found} card payments found. Run again with --apply to link them.");

        return self::SUCCESS;
    }
}
