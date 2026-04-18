<?php

namespace Hermod\Rpc;

use Hermod\Exceptions\RpcException;

class PendingCallRegistry
{
    /** @var array<int, PendingCall> */
    private array $pending = [];

    public function __construct(
        private readonly RequestIdGenerator $idGenerator,
    ) {}

    /**
     * Registra una nuova chiamata pendente e restituisce il requestId.
     */
    public function register(string $procedure): PendingCall
    {
        $requestId = $this->generateUniqueId();

        $call = new PendingCall(
            requestId: $requestId,
            procedure: $procedure,
        );

        $this->pending[$requestId] = $call;

        return $call;
    }

    /**
     * Recupera una chiamata pendente per requestId.
     */
    public function get(int $requestId): PendingCall
    {
        if (! isset($this->pending[$requestId])) {
            throw new RpcException(
                "Nessuna chiamata pendente trovata per requestId: {$requestId}",
            );
        }

        return $this->pending[$requestId];
    }

    /**
     * Rimuove e restituisce una chiamata pendente.
     */
    public function pull(int $requestId): PendingCall
    {
        $call = $this->get($requestId);
        unset($this->pending[$requestId]);

        return $call;
    }

    /**
     * Rigetta tutte le chiamate pendenti — usato in caso di disconnessione.
     */
    public function rejectAll(\Throwable $e): void
    {
        foreach ($this->pending as $call) {
            $call->reject($e);
        }

        $this->pending = [];
    }

    public function count(): int
    {
        return count($this->pending);
    }

    public function isEmpty(): bool
    {
        return empty($this->pending);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function generateUniqueId(): int
    {
        // Evita collisioni nel (raro) caso di ID duplicato
        do {
            $id = $this->idGenerator->generate();
        } while (isset($this->pending[$id]));

        return $id;
    }
}
