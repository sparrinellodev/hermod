<?php

namespace Hermod\Auth;

use Hermod\Contracts\AuthenticatorContract;

class AnonymousAuthenticator implements AuthenticatorContract
{
    /**
     * Summary of method
     */
    public function method(): AuthMethod
    {
        return AuthMethod::Anonymous;
    }

    /**
     * Summary of authId
     *
     * @return null
     */
    public function authId(): ?string
    {
        return null;
    }

    /**
     * Summary of authExtra
     * @return array<mixed>
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
        return null; // anonymous non gestisce challenge
    }

    /**
     * Summary of requiresChallenge
     */
    public function requiresChallenge(): bool
    {
        return false;
    }
}
