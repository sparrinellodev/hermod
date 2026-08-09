<?php

namespace Hermod\LaravelWamp\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a WAMP protocol violation occurs.
 *
 * This includes receiving malformed messages, unexpected message sequences 
 * according to the WAMP specification, or protocol-level errors returned by the router.
 */
class WampProtocolException extends RuntimeException
{
    /**
     * Create a new WampProtocolException instance.
     *
     * @param  string  $message  The error message.
     * @param  string  $wampError  The raw WAMP error URI (e.g., 'wamp.error.protocol_violation').
     * @param  array<string, mixed>  $details  Additional error details associated with the protocol violation.
     * @param  \Throwable|null  $previous  The previous throwable used for exception chaining.
     */
    public function __construct(
        string $message,
        public readonly string $wampError = '',
        public readonly array $details = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}