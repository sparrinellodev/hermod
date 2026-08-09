<?php

namespace Hermod\LaravelWamp\Transport;

use Amp\Websocket\Client\Rfc6455Connector;
use Hermod\LaravelWamp\Contracts\SerializerContract;
use Hermod\LaravelWamp\Contracts\TransportContract;

/**
 * Factory class responsible for instantiating WebSocketTransport instances.
 *
 * Encapsulates the creation logic for WAMP WebSocket client transport connections 
 * by pairing an AMPHP RFC6455-compliant WebSocket connector with target connection URLs 
 * and protocol serializers.
 */
class WebSocketTransportFactory
{
    /**
     * Create and return a new WebSocketTransport instance configured with the given parameters.
     *
     * @param  string  $url  The WebSocket connection URL (ws:// or wss://).
     * @param  \Hermod\LaravelWamp\Contracts\SerializerContract  $serializer  The protocol serializer implementation.
     * @return TransportContract The newly instantiated WebSocket transport layer.
     */
    public function make(
        string $url,
        SerializerContract $serializer,
    ): TransportContract {
        return new WebSocketTransport(
            connector: new Rfc6455Connector,
            serializer: $serializer,
            url: $url,
        );
    }
}