<?php

namespace App\Domains\Integration\Concerns;

use App\Domains\Automation\Models\Automation;

interface MailToTransaction
{
    public function handle(Automation $automation, mixed $mail, int $index): TransactionDataDTO | null;

    public static function getSchema(): mixed;
}
