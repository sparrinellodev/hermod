<?php

namespace Hermod\LaravelWamp\Contracts;

/**
 * Defines the contract for handling incoming Remote Procedure Calls (RPC).
 *
 * A Callee registers procedures on the WAMP router and executes the 
 * corresponding handlers when invoked by remote callers.
 */
interface CalleeContract
{
    /**
     * Register a procedure on the WAMP router.
     *
     * @param  string  $procedure  The URI of the procedure (e.g., 'com.myapp.sum').
     * @param  callable  $handler  The function or method that handles incoming invocations.
     */
    public function register(string $procedure, callable $handler): void;

    /**
     * Unregister a previously registered procedure from the WAMP router.
     *
     * @param  string  $procedure  The URI of the procedure to unregister.
     */
    public function unregister(string $procedure): void;

    /**
     * Get all currently active local procedure registrations.
     *
     * @return array<string, callable> An associative array mapping procedure URIs to their handlers.
     */
    public function getRegistrations(): array;
}