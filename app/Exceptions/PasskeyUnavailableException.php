<?php

namespace App\Exceptions;

use Exception;

class PasskeyUnavailableException extends Exception
{
    public function __construct(string $message = 'Passkey sign-in is not available.')
    {
        parent::__construct($message);
    }
}
