<?php

namespace Hermod\LaravelWamp\Auth;

use Hermod\LaravelWamp\Contracts\AuthenticatorContract;
use Hermod\LaravelWamp\Exceptions\AuthenticationException;

/**
 * Handles Challenge-Response Authentication (WAMP-CRA) for the connection.
 *
 * This method provides a secure way to authenticate without sending the secret
 * over the wire. It uses HMAC-SHA256 to sign a challenge provided by the router.
 * It also supports salted passwords using PBKDF2 key derivation.
 */
class WampCraAuthenticator implements AuthenticatorContract
{
    /**
     * Create a new WAMP-CRA Authenticator instance.
     *
     * @param  string  $authId  The authentication ID (e.g., username).
     * @param  string  $secret  The shared secret or password.
     */
    public function __construct(
        private readonly string $authId,
        private readonly string $secret,
    ) {
    }

    /**
     * Get the WAMP authentication method type.
     *
     * @return \Hermod\LaravelWamp\Auth\AuthMethod
     */
    public function method(): AuthMethod
    {
        return AuthMethod::WampCra;
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
     *
     * @param  string  $challenge  The challenge string provided by the router.
     * @param  array<string, mixed>  $extra  Additional challenge details (e.g., salt, iterations).
     * @return string|null The base64-encoded HMAC signature.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\AuthenticationException If the challenge is empty.
     */
    public function handleChallenge(string $challenge, array $extra = []): ?string
    {
        if (empty($challenge)) {
            throw new AuthenticationException(
                'WAMP-CRA: Empty challenge received from the router.'
            );
        }

        return $this->sign($challenge, $this->resolveKey($extra));
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Sign the challenge using HMAC-SHA256.
     *
     * @param  string  $challenge  The challenge string provided by the router.
     * @param  string  $key        The resolved cryptographic key.
     * @return string The base64-encoded signature.
     */
    private function sign(string $challenge, string $key): string
    {
        $hmac = hash_hmac('sha256', $challenge, $key, binary: true);

        return base64_encode($hmac);
    }

    /**
     * Resolve the cryptographic key to use for signing.
     *
     * If the router provides a salt, the key is derived using PBKDF2.
     * Otherwise, the plaintext secret is used directly.
     *
     * @param  array<string, mixed>  $extra  The extra dictionary from the challenge message.
     * @return string The resolved key (base64-encoded if salted, plaintext otherwise).
     */
    private function resolveKey(array $extra): string
    {
        if (empty($extra['salt'])) {
            return $this->secret;
        }

        // Salted WAMP-CRA: derive the key using PBKDF2
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