<?php

namespace Hermod\Contracts;

interface WampClientContract extends CalleeContract, CallerContract
{
    /**
     * Connette al router e stabilisce la sessione WAMP.
     */
    public function connect(): void;

    /**
     * Disconnette dal router chiudendo la sessione.
     */
    public function disconnect(): void;

    /**
     * Indica se il client è connesso e la sessione è stabilita.
     */
    public function isConnected(): bool;
}
