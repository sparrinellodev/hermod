<?php

namespace Hermod\Exceptions;

class RpcException extends \RuntimeException
{
    public function __construct(
        string                  $message,
        public readonly string  $wampError = '',
        public readonly array   $args = [],
        public readonly array   $kwargs = [],
        ?\Throwable             $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}
