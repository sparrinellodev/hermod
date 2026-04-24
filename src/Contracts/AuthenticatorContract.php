<?php

namespace Hermod\Contracts;

use Hermod\Auth\AuthMethod;

interface AuthenticatorContract
{
    /**
     * Restituisce il metodo di autenticazione WAMP.
     */
    public function method(): AuthMethod;

    /**
     * Restituisce l'authid da inviare nel messaggio HELLO.
     *
     * @return void
     */
    public function authId(): ?string;

    /**
     * Restituisce i dettagli extra da includere nel HELLO.
     */
    public function authExtra(): array;

    /**
     * Restituisce il payload di risposta da includere in AUTHENTICATE.
     *
     * @return void
     */
    public function handleChallenge(string $challenge, array $extra = []): ?string;

    /**
     * Indica se questo metodo richiede un messaggio AUTHENTICATE
     */
    public function requiresChallenge(): bool;
}
