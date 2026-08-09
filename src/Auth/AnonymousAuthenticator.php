<?php

namespace Hermod\LaravelWamp\Auth;

use Hermod\LaravelWamp\Contracts\AuthenticatorContract;

/**
 * Handles anonymous authentication for the WAMP connection.
 * * This is the default authentication method when no specific
 * credentials are provided or required by the router.
 */
class AnonymousAuthenticator implements AuthenticatorContract
{
    /**
     * Get the WAMP authentication method type.
     *
     * @return \Hermod\LaravelWamp\Auth\AuthMethod
     */
    public function method(): AuthMethod
    {
        return AuthMethod::Anonymous;
    }

    /**
     * Get the authentication ID (authid) for the connection.
     * * Anonymous connections do not require an authid.
     *
     * @return string|null
     */
    public function authId(): ?string
    {
        return null;
    }

    /**
     * Get extra authentication parameters to be sent during the HELLO message.
     *
     * @return array<string, mixed>
     */
    public function authExtra(): array
    {
        return [];
    }

    /**
     * Handle the authentication challenge issued by the WAMP router.
     * * The anonymous method does not receive or handle challenges,
     * therefore this method always returns null.
     *
     * @param  string  $challenge  The challenge string provided by the router.
     * @param  array<string, mixed>  $extra  Additional challenge details.
     * @return string|null The computed signature, or null if not applicable.
     */
    public function handleChallenge(string $challenge, array $extra = []): ?string
    {
        return null;
    }

    /**
     * Determine if this authentication method requires a challenge-response sequence.
     *
     * @return bool
     */
    public function requiresChallenge(): bool
    {
        return false;
    }
}