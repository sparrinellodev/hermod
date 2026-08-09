<?php

namespace Hermod\LaravelWamp\Contracts;

use Amp\Future;

/**
 * Defines the contract for initiating Remote Procedure Calls (RPC) as a Caller.
 *
 * A Caller can invoke remote procedures synchronously (blocking until a response 
 * is received) or asynchronously using AMPHP futures.
 */
interface CallerContract
{
    /**
     * Synchronously call a remote procedure and return the result.
     * * This method blocks execution until the response is received from the router.
     *
     * @param  string  $procedure  The URI of the procedure to call (e.g., 'com.myapp.compute').
     * @param  array<mixed>  $args       Positional arguments for the procedure.
     * @param  array<string, mixed>  $kwargs  Keyword arguments for the procedure.
     * @return mixed The result returned by the remote procedure.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\WampClientException If the call fails or connection is lost.
     */
    public function call(string $procedure, array $args = [], array $kwargs = []): mixed;

    /**
     * Asynchronously call a remote procedure and return an Amp Future.
     * * This allows non-blocking execution, resolving when the result becomes available.
     *
     * @param  string  $procedure  The URI of the procedure to call (e.g., 'com.myapp.compute').
     * @param  array<mixed>  $args       Positional arguments for the procedure.
     * @param  array<string, mixed>  $kwargs  Keyword arguments for the procedure.
     * @return Future<mixed> A future that resolves to the result of the remote call.
     */
    public function callAsync(string $procedure, array $args = [], array $kwargs = []): Future;
}