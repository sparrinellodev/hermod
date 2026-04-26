<?php

namespace Hermod\Transport;

use Hermod\Contracts\SerializerContract;
use Hermod\Contracts\TransportContract;
use Hermod\Transport\RawSocket\RawSocketTransport;

class RawSocketTransportFactory
{
    /**
     * Summary of make
     *
     * @return RawSocketTransport
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
