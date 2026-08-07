<?php

namespace App\Exceptions;

use Exception;

class WhatsAppRetryableException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
    ) {
        parent::__construct($message);
    }
}
