<?php

namespace Hermod\LaravelWamp\Exceptions;

use Throwable;

/**
 * Exception thrown when a WAMP authentication error occurs.
 *
 * This can include failures during the challenge-response sequence,
 * unsupported authentication methods, or rejected credentials.
 */
class AuthenticationException extends \RuntimeException
{
    /**
     * Create a new AuthenticationException instance.
     *
     * @param  string  $message  The error message.
     * @param  string  $wampError  The raw WAMP error URI (e.g., 'wamp.error.no_such_realm').
     * @param  array<string, mixed>  $details  Additional error details provided by the router.
     * @param  \Throwable|null  $previous  The previous throwable used for exception chaining.
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