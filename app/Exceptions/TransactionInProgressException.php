<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a transaction notification is received but the transaction
 * is still in progress and not yet confirmed.
 *
 * This is expected behavior - the transaction will be processed when the
 * confirmation email arrives.
 */
class TransactionInProgressException extends Exception
{
    public function __construct(string $message = 'Transaction is in progress - waiting for confirmation email')
    {
        parent::__construct($message);
    }
}
