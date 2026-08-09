<?php

namespace Hermod\LaravelWamp\Contracts;

/**
 * Defines the contract for underlying transport channels (e.g., WebSockets, RawSockets).
 *
 * The transport layer handles the low-level network communication, sending and 
 * receiving serialized data streams between the client and the WAMP router.
 */
interface TransportContract
{
    /**
     * Open the underlying network connection (e.g., WebSocket handshake).
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If the connection cannot be established.
     */
    public function connect(): void;

    /**
     * Send a raw serialized payload to the WAMP router.
     *
     * @param  string  $data  The raw serialized data string to transmit.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If writing to the transport fails.
     */
    public function send(string $data): void;

    /**
     * Receive the next raw payload string from the WAMP router.
     * * This may block depending on the transport configuration until data arrives.
     *
     * @return string The raw incoming payload string.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If reading from the transport fails.
     */
    public function receive(): string;

    /**
     * Close the transport connection gracefully.
     */
    public function close(): void;

    /**
     * Determine if the underlying transport connection is currently active.
     *
     * @return bool True if connected, false otherwise.
     */
    public function isConnected(): bool;
}