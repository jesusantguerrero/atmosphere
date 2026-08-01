<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\EntryGenerated;
use App\Notifications\TransactionsImported;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

/**
 * One-off cleanup for the notification spam that predated the dedupe fix in
 * TransactionCreateEntry / ImportTransactions job. Collapses each user's
 * unread EntryGenerated / TransactionsImported queue down to the most recent
 * one (marks the rest as read). Safe to re-run: after the code fix the queue
 * shouldn't accumulate again, so subsequent runs will do nothing.
 *
 *   php artisan notifications:dedupe-imports
 *   php artisan notifications:dedupe-imports --dry
 */
class DedupeImportNotifications extends Command
{
    protected $signature = 'notifications:dedupe-imports
        {--dry : Report what would be marked read without touching the DB}';

    protected $description = 'Collapse per-user unread import notifications down to the most recent one';

    /** Types this command collapses. Add here if we introduce more spammy types. */
    private array $spammyTypes = [
        EntryGenerated::class,
        TransactionsImported::class,
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry');
        $totalCollapsed = 0;

        foreach (User::query()->cursor() as $user) {
            foreach ($this->spammyTypes as $type) {
                $unread = $user->unreadNotifications()
                    ->where('type', $type)
                    ->orderByDesc('created_at')
                    ->get();

                if ($unread->count() <= 1) {
                    continue;
                }

                $keep = $unread->first();
                $collapse = $unread->skip(1);

                $this->line(sprintf(
                    '  user=%d type=%s  keep=%s  collapse=%d',
                    $user->id,
                    class_basename($type),
                    $keep->id,
                    $collapse->count()
                ));

                if (! $dryRun) {
                    DatabaseNotification::whereIn('id', $collapse->pluck('id'))
                        ->update(['read_at' => now()]);
                }

                $totalCollapsed += $collapse->count();
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? "Dry-run complete. Would mark {$totalCollapsed} notification(s) as read."
            : "Collapsed {$totalCollapsed} duplicate notification(s)."
        );

        return self::SUCCESS;
    }
}
