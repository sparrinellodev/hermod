<?php

namespace Hermod\Auth;

use Hermod\Contracts\AuthenticatorContract;

class TicketAuthenticator implements AuthenticatorContract
{
    /**
     * Summary of __construct
     */
    public function __construct(
        private readonly string $authId,
        private readonly string $ticket,
    ) {}

    /**
     * Summary of method
     */
    public function method(): AuthMethod
    {
        return AuthMethod::Ticket;
    }

    /**
     * Summary of authId
     */
    public function authId(): ?string
    {
        return $this->authId;
    }

    /**
     * Summary of authExtra
     * * @return array<mixed>
     */
    public function authExtra(): array
    {
        return [];
    }

    /**
     * Summary of handleChallenge
     * @param string $challenge
     * @param array<string, mixed> $extra
     * @return string|null
     */
    public function handleChallenge(string $challenge, array $extra = []): ?string
    {
        return $this->ticket;
    }

    /**
     * Summary of requiresChallenge
     */
    public function requiresChallenge(): bool
    {
        return true;
    }
}
