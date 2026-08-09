<?php

namespace Hermod\LaravelWamp\Contracts;

/**
 * Defines the main contract for the WampClient.
 *
 * This master interface combines all WAMP feature contracts (Callee, Caller, 
 * Publisher, Subscriber) alongside core connection and session lifecycle methods.
 */
interface WampClientContract extends CalleeContract, CallerContract, PublisherContract, SubscriberContract
{
    /**
     * Connect to the WAMP router and establish the session.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\AuthenticationException If authentication fails.
     * @throws \Hermod\LaravelWamp\Exceptions\WampClientException If the connection cannot be established.
     */
    public function connect(): void;

    /**
     * Disconnect from the WAMP router by gracefully closing the session.
     */
    public function disconnect(): void;

    /**
     * Determine if the client is connected and the session is successfully established.
     *
     * @return bool True if connected and active, false otherwise.
     */
    public function isConnected(): bool;
}