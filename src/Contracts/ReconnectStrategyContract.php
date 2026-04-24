<?php

namespace Hermod\Contracts;

interface ReconnectStrategyContract
{
    /**
     * Indica se deve essere tentato un altro reconnect.
     */
    public function shouldRetry(): bool;

    /**
     * Restituisce il delay in secondi prima del prossimo tentativo.
     */
    public function nextDelay(): float;

    /**
     * Registra un tentativo di reconnect fallito.
     */
    public function recordFailure(): void;

    /**
     * Resetta il contatore dei tentativi (dopo reconnect riuscito).
     */
    public function reset(): void;

    /**
     * Restituisce il numero di tentativi effettuati finora.
     */
    public function attempts(): int;
}
