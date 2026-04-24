<?php

namespace Hermod\Session;

use Hermod\Contracts\AuthenticatorContract;
use Hermod\Contracts\SerializerContract;
use Hermod\Contracts\TransportContract;

class WampSessionFactory
{
    /**
     * Summary of make
     */
    public function make(
        TransportContract $transport,
        SerializerContract $serializer,
        string $realm,
        AuthenticatorContract $authenticator,
    ): WampSession {
        return new WampSession(
            transport: $transport,
            serializer: $serializer,
            realm: $realm,
            authenticator: $authenticator,
        );
    }
}
