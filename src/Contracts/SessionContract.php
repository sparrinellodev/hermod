<?php

namespace Hermod\Contracts;

interface SessionContract
{
    /**
     * Avvia la sessione WAMP inviando HELLO al router.
     */
    public function hello(): void;

    /**
     * Chiude la sessione WAMP inviando GOODBYE al router.
     */
    public function goodbye(): void;

    /**
     * Restituisce il session ID assegnato dal router dopo WELCOME.
     */
    public function getSessionId(): ?int;

    /**
     * Restituisce il realm corrente.
     */
    public function getRealm(): string;

    /**
     * Indica se la sessione è stabilita (WELCOME ricevuto).
     */
    public function isEstablished(): bool;
}
