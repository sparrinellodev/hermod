<?php

namespace Hermod\Exceptions;

class RpcException extends \RuntimeException
{
    /** @param array<mixed> $args
     * @param  array<mixed>  $kwargs
     */
    public function __construct(
        string $message,
        public readonly string $wampError = '',
        public readonly array $args = [],
        public readonly array $kwargs = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}
