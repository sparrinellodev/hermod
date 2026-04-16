<?php

namespace Hermod\Exceptions;

use RuntimeException;

class WampProtocolException extends RuntimeException
{
    public function __construct(
        string                    $message,
        public readonly string    $wampError = '',
        public readonly array     $details = [],
        ?\Throwable               $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}
