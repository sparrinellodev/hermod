<?php

namespace Hermod\LaravelWamp\Contracts;

use Hermod\LaravelWamp\Auth\AuthMethod;

/**
 * Defines the contract for WAMP authentication methods.
 *
 * Any custom authentication strategy (Anonymous, Ticket, WAMP-CRA, etc.)
 * must implement this interface to be utilized by the WampClient.
 */
interface AuthenticatorContract
{
    /**
     * Get the WAMP authentication method type.
     *
     * @return \Hermod\LaravelWamp\Auth\AuthMethod
     */
    public function method(): AuthMethod;

    /**
     * Get the authentication ID (authid) to be sent in the HELLO message.
     *
     * @return string|null
     */
    public function authId(): ?string;

    /**
     * Get extra authentication details to be included in the HELLO message.
     *
     * @return array<string, mixed>
     */
    public function authExtra(): array;

    /**
     * Compute the signature or response to be sent in the AUTHENTICATE message.
     *
     * @param  string  $challenge  The challenge string provided by the router.
     * @param  array<string, mixed>  $extra  Additional challenge details (e.g., salt, iterations).
     * @return string|null The computed signature, or null if not applicable.
     */
    public function handleChallenge(string $challenge, array $extra = []): ?string;

    /**
     * Determine if this authentication method requires a challenge-response sequence.
     *
     * @return bool True if the method requires an AUTHENTICATE message, false otherwise.
     */
    public function requiresChallenge(): bool;
}