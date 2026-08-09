<?php

namespace Hermod\LaravelWamp\Contracts;

/**
 * Defines the contract for managing a WAMP session lifecycle.
 *
 * A session represents the transient conversation between a WAMP client and router, 
 * established over a transport channel and associated with a specific realm.
 */
interface SessionContract
{
    /**
     * Start the WAMP session by sending the HELLO message to the router.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\AuthenticationException If authentication fails.
     * @throws \Hermod\LaravelWamp\Exceptions\WampProtocolException If the handshake or greeting is rejected.
     */
    public function hello(): void;

    /**
     * Gracefully close the WAMP session by sending the GOODBYE message to the router.
     */
    public function goodbye(): void;

    /**
     * Get the unique session ID assigned by the router upon a successful WELCOME message.
     *
     * @return int|null The session ID, or null if the session is not yet established.
     */
    public function getSessionId(): ?int;

    /**
     * Get the routing realm associated with this session.
     *
     * @return string The realm name.
     */
    public function getRealm(): string;

    /**
     * Determine if the session has been successfully established (WELCOME received).
     *
     * @return bool True if the session is active and established, false otherwise.
     */
    public function isEstablished(): bool;
}