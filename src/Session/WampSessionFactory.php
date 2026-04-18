<?php

namespace Hermod\Session;

use Hermod\Contracts\SerializerContract;
use Hermod\Contracts\TransportContract;

class WampSessionFactory
{
    public function make(
        TransportContract $transport,
        SerializerContract $serializer,
        string $realm,
    ): WampSession {
        return new WampSession(
            transport: $transport,
            serializer: $serializer,
            realm: $realm,
        );
    }
}
