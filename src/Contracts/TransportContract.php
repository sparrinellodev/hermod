<?php

namespace Hermod\Contracts;

interface TransportContract
{
    /**
     * Apre la connessione WebSocket.
     */
    public function connect(): void;

    /**
     * Invia un messaggio raw al router.
     */
    public function send(string $data): void;

    /**
     * Riceve il prossimo messaggio raw dal router.
     */
    public function receive(): string;

    /**
     * Chiude la connessione.
     */
    public function close(): void;

    /**
     * Indica se la connessione è attiva.
     */
    public function isConnected(): bool;
}
