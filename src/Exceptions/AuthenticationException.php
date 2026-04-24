<?php

namespace Hermod\Exceptions;

use Throwable;

class AuthenticationException extends \RuntimeException
{
    /**
     * Summary of __construct
     *
     * @param  mixed  $previous
     */
    public function __construct(
        string $message,
        public readonly string $wampError = '',
        public readonly array $details = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}
