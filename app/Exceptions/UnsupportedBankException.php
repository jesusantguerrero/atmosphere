<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a bank code is not supported by the parser registry.
 *
 * This exception is used in the UniversalBankParser when attempting to get
 * a parser for a bank that hasn't been registered in the system.
 */
class UnsupportedBankException extends Exception
{
    /**
     * Create a new UnsupportedBankException instance.
     *
     * @param  string  $bankCode  The unsupported bank code
     */
    public function __construct(string $bankCode)
    {
        parent::__construct("Bank '{$bankCode}' is not supported by the parser registry");
    }
}
