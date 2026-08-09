<?php

namespace Hermod\LaravelWamp\Contracts;

/**
 * Defines the contract for WAMP message serialization and deserialization.
 *
 * Serializers handle the conversion of WAMP message arrays into raw payloads 
 * suitable for transmission over WebSocket transports (e.g., JSON, MessagePack, CBOR).
 */
interface SerializerContract
{
    /**
     * Serialize a WAMP message array into a raw string to be sent via the transport.
     *
     * @param  array<mixed>  $message  The raw WAMP message data structure.
     * @return string The serialized payload string.
     */
    public function serialize(array $message): string;

    /**
     * Deserialize a raw string received from the transport back into a WAMP message array.
     *
     * @param  string  $raw  The raw incoming payload string.
     * @return array<mixed> The decoded WAMP message array.
     */
    public function deserialize(string $raw): array;

    /**
     * Get the WebSocket subprotocol identifier to negotiate during the handshake.
     *
     * Examples: 'wamp.2.json', 'wamp.2.msgpack', 'wamp.2.cbor'
     *
     * @return string The subprotocol string.
     */
    public function subprotocol(): string;
}