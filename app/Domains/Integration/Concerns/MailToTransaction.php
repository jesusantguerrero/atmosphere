<?php

namespace App\Domains\Integration\Concerns;

use App\Domains\Integration\Models\Automation;

interface MailToTransaction {
   public function handle(Automation $automation, mixed $mail, int $index): TransactionDataDTO;

   public static function getSchema(): mixed;
}
