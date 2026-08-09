<?php

namespace Hermod\LaravelWamp\Transport;

use Hermod\LaravelWamp\Contracts\SerializerContract;
use Hermod\LaravelWamp\Contracts\TransportContract;
use Hermod\LaravelWamp\Transport\RawSocket\RawSocketTransport;

/**
 * Factory class responsible for instantiating RawSocketTransport instances.
 *
 * Encapsulates the creation logic for WAMP RawSocket transport connections by 
 * accepting connection endpoints (URLs), protocol serializers, and connection timeout thresholds.
 */
class RawSocketTransportFactory
{
    /**
     * Create and return a new RawSocketTransport instance configured with the given parameters.
     *
     * @param  string  $url  The socket connection URL (e.g., 'tcp://127.0.0.1:8080' or 'unix:///path/to/socket').
     * @param  \Hermod\LaravelWamp\Contracts\SerializerContract  $serializer  The protocol serializer implementation.
     * @param  float  $connectTimeout  The connection timeout threshold in seconds.
     * @return TransportContract The newly instantiated RawSocket transport layer.
     */
    public function make(
        string $url,
        SerializerContract $serializer,
        float $connectTimeout = 10.0,
    ): TransportContract {
        return new RawSocketTransport(
            serializer: $serializer,
            url: $url,
            connectTimeout: $connectTimeout,
        );
    }
}