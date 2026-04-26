<?php

namespace Hermod\Auth;

use Hermod\Contracts\AuthenticatorContract;
use Hermod\Exceptions\AuthenticationException;

class WampCraAuthenticator implements AuthenticatorContract
{
    /**
     * Summary of __construct
     */
    public function __construct(
        private readonly string $authId,
        private readonly string $secret,
    ) {}

    /**
     * Summary of method
     */
    public function method(): AuthMethod
    {
        return AuthMethod::WampCra;
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
     *
     * @return array<string, mixed>
     */
    public function authExtra(): array
    {
        return [];
    }

    /**
     * Summary of handleChallenge
     *
     * @param  array<mixed>  $extra
     *
     * @throws AuthenticationException
     */
    public function handleChallenge(string $challenge, array $extra = []): ?string
    {
        if (empty($challenge)) {
            throw new AuthenticationException(
                'WAMP-CRA: challenge vuota ricevuta dal router.',
            );
        }

        return $this->sign($challenge, $this->resolveKey($extra));
    }

    /**
     * Summary of requiresChallenge
     */
    public function requiresChallenge(): bool
    {
        return true;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Summary of sign
     */
    private function sign(string $challenge, string $key): string
    {
        $hmac = hash_hmac('sha256', $challenge, $key, binary: true);

        return base64_encode($hmac);
    }

    /**
     * Summary of resolveKey
     *
     * @param  array<mixed>  $extra
     */
    private function resolveKey(array $extra): string
    {
        if (empty($extra['salt'])) {
            return $this->secret;
        }

        // WAMP-CRA salted: deriva la chiave con PBKDF2
        $salt = (string) $extra['salt'];
        $iterations = (int) ($extra['iterations'] ?? 1000);
        $keyLength = (int) ($extra['keylen'] ?? 32);

        $derived = hash_pbkdf2(
            algo: 'sha256',
            password: $this->secret,
            salt: $salt,
            iterations: $iterations,
            length: $keyLength,
            binary: true,
        );

        return base64_encode($derived);
    }
}
