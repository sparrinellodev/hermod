<?php

namespace Hermod\LaravelWamp\Exceptions;

/**
 * Exception thrown when an error occurs during Remote Procedure Call (RPC) operations.
 *
 * This includes remote execution errors returned by the callee, invocation failures,
 * or errors related to pending RPC calls (e.g., call timeouts or cancellations).
 */
class RpcException extends \RuntimeException
{
    /**
     * Create a new RpcException instance.
     *
     * @param  string  $message  The error message.
     * @param  string  $wampError  The raw WAMP error URI (e.g., 'com.myapp.error.invalid_arguments').
     * @param  array<mixed>  $args  Positional arguments returned with the error details.
     * @param  array<string, mixed>  $kwargs  Keyword arguments returned with the error details.
     * @param  \Throwable|null  $previous  The previous throwable used for exception chaining.
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