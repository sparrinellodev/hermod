<?php

namespace Hermod\LaravelWamp\Rpc;

use Amp\DeferredFuture;
use Amp\Future;

/**
 * Represents a pending asynchronous remote procedure call (RPC).
 *
 * Encapsulates the unique request ID, the target procedure URI, and an AMPHP DeferredFuture 
 * instance used to asynchronously resolve or reject the call when the response arrives.
 */
class PendingCall
{
    /** @var DeferredFuture<mixed> The deferred future managing asynchronous completion. */
    private readonly DeferredFuture $deferred;

    /**
     * Create a new PendingCall instance.
     *
     * @param  int  $requestId  The unique request ID identifying this call on the wire.
     * @param  string  $procedure  The URI of the procedure being invoked.
     */
    public function __construct(
        public readonly int $requestId,
        public readonly string $procedure,
    ) {
        $this->deferred = new DeferredFuture;
    }

    /**
     * Return the Future that will resolve with the RPC result.
     *
     * @return Future<mixed> The asynchronous future instance.
     */
    public function getFuture(): Future
    {
        return $this->deferred->getFuture();
    }

    /**
     * Resolve the call with the successful result received from the router.
     *
     * @param  mixed  $result  The payload returned by the remote callee.
     */
    public function resolve(mixed $result): void
    {
        $this->deferred->complete($result);
    }

    /**
     * Reject the call with an exception when an error occurs.
     *
     * @param  \Throwable  $e  The exception detailing the failure.
     */
    public function reject(\Throwable $e): void
    {
        $this->deferred->error($e);
    }
}