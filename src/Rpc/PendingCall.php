<?php

namespace Hermod\Rpc;

use Amp\DeferredFuture;
use Amp\Future;

class PendingCall
{
    /** @var DeferredFuture<mixed> */
    private readonly DeferredFuture $deferred;

    public function __construct(
        public readonly int $requestId,
        public readonly string $procedure,
    ) {
        $this->deferred = new DeferredFuture;
    }

    /**
     * Restituisce il Future che si risolverà con il risultato RPC.
     *
     * @return Future<mixed>
     */
    public function getFuture(): Future
    {
        return $this->deferred->getFuture();
    }

    /**
     * Risolve la chiamata con il risultato ricevuto dal router.
     */
    public function resolve(mixed $result): void
    {
        $this->deferred->complete($result);
    }

    /**
     * Rigetta la chiamata con un'eccezione.
     */
    public function reject(\Throwable $e): void
    {
        $this->deferred->error($e);
    }
}
