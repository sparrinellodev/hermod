<?php

namespace Hermod\LaravelWamp\Auth;

use Hermod\LaravelWamp\Contracts\AuthenticatorContract;

/**
 * Handles ticket-based authentication for the WAMP connection.
 * * This method uses a single pre-shared secret (the ticket), such as an API key 
 * or a static JWT. The ticket is sent back to the router exactly as-is 
 * when the challenge is received.
 */
class TicketAuthenticator implements AuthenticatorContract
{
    /**
     * Create a new Ticket Authenticator instance.
     *
     * @param  string  $authId  The authentication ID (e.g., username or client ID).
     * @param  string  $ticket  The pre-shared secret token.
     */
    public function __construct(
        private readonly string $authId,
        private readonly string $ticket,
    ) {
    }

    /**
     * Get the WAMP authentication method type.
     *
     * @return \Hermod\LaravelWamp\Auth\AuthMethod
     */
    public function method(): AuthMethod
    {
        return AuthMethod::Ticket;
    }

    /**
     * Get the authentication ID (authid) for the connection.
     *
     * @return string|null
     */
    public function authId(): ?string
    {
        return $this->authId;
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
     * * For ticket authentication, the response to the challenge is simply 
     * the ticket itself, which is sent back to the router as the signature.
     *
     * @param  string  $challenge  The challenge string provided by the router (usually empty for ticket auth).
     * @param  array<string, mixed>  $extra  Additional challenge details.
     * @return string|null The ticket token used as the signature.
     */
    public function handleChallenge(string $challenge, array $extra = []): ?string
    {
        return $this->ticket;
    }

    /**
     * Determine if this authentication method requires a challenge-response sequence.
     *
     * @return bool
     */
    public function requiresChallenge(): bool
    {
        return true;
    }
}