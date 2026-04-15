<?php

namespace Hermod\Contracts;

interface SerializerContract
{
    /**
     * Serializza un messaggio WAMP in stringa da inviare via WebSocket.
     */
    public function serialize(array $message): string;

    /**
     * Deserializza la stringa raw ricevuta in array WAMP.
     */
    public function deserialize(string $raw): array;

    /**
     * Restituisce il subprotocol WebSocket da negoziare nell'handshake.
     * Es: wamp.2.json | wamp.2.msgpack | wamp.2.cbor
     */
    public function subprotocol(): string;
}