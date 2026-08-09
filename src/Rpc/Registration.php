<?php

namespace Hermod\LaravelWamp\Rpc;

/**
 * Value object representing an active WAMP procedure registration.
 *
 * Encapsulates the unique router-assigned registration ID, the procedure URI, 
 * and the callback handler invoked when remote clients invoke the procedure.
 */
class Registration
{
    /** @var callable The callback handler executed upon remote procedure invocation. */
    public readonly mixed $handler;

    /**
     * Create a new Registration instance.
     *
     * @param  int  $registrationId  The unique registration ID assigned by the WAMP router.
     * @param  string  $procedure  The URI of the registered procedure.
     * @param  callable  $handler  The local callback executed when invoked.
     */
    public function __construct(
        public readonly int $registrationId,
        public readonly string $procedure,
        callable $handler,
    ) {
        $this->handler = $handler;
    }
}