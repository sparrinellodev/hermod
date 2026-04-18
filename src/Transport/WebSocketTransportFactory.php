<?php

namespace Hermod\Transport;

use Amp\Websocket\Client\Rfc6455Connector;
use Hermod\Contracts\SerializerContract;
use Hermod\Contracts\TransportContract;

class WebSocketTransportFactory
{
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
