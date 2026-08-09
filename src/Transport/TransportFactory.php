<?php

namespace Hermod\LaravelWamp\Transport;

use Hermod\LaravelWamp\Contracts\SerializerContract;
use Hermod\LaravelWamp\Contracts\TransportContract;
use Hermod\LaravelWamp\Exceptions\TransportException;

/**
 * Factory class responsible for instantiating WAMP transport layers dynamically.
 *
 * Evaluates transport configuration types (e.g., 'websocket', 'rawsocket') and delegates 
 * creation to specialized transport factories, injecting endpoints and serializers appropriately.
 */
class TransportFactory
{
    /**
     * Create a new TransportFactory instance.
     *
     * @param  \Hermod\LaravelWamp\Transport\WebSocketTransportFactory  $websocketFactory  The WebSocket transport factory.
     * @param  \Hermod\LaravelWamp\Transport\RawSocketTransportFactory  $rawSocketFactory  The RawSocket transport factory.
     */
    public function __construct(
        private readonly WebSocketTransportFactory $websocketFactory,
        private readonly RawSocketTransportFactory $rawSocketFactory,
    ) {
    }

    /**
     * Instantiate and return the correct transport layer implementation based on configuration.
     * Supported types:
     * - 'websocket' → WebSocketTransport (ws:// or wss://)
     * - 'rawsocket' → RawSocketTransport (tcp:// or unix://)
     *
     * @param  string  $type  The transport driver type identifier.
     * @param  string  $url  The connection endpoint URL.
     * @param  \Hermod\LaravelWamp\Contracts\SerializerContract  $serializer  The protocol serializer implementation.
     * @return TransportContract The instantiated transport layer.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If the transport type is unsupported.
     */
    public function make(
        string $type,
        string $url,
        SerializerContract $serializer,
    ): TransportContract {
        return match ($type) {
            'websocket' => $this->websocketFactory->make(
                url: $url,
                serializer: $serializer,
            ),
            'rawsocket' => $this->rawSocketFactory->make(
                url: $url,
                serializer: $serializer,
            ),
            default => throw new TransportException(
                "Unsupported transport type: '{$type}'. " .
                'Accepted values: websocket, rawsocket',
            ),
        };
    }
}