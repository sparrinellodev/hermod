<?php

namespace Hermod\LaravelWamp\Session;

use Hermod\LaravelWamp\Contracts\AuthenticatorContract;
use Hermod\LaravelWamp\Contracts\SerializerContract;
use Hermod\LaravelWamp\Contracts\TransportContract;

/**
 * Factory class responsible for instantiating WampSession instances.
 *
 * Encapsulates the creation logic for WAMP client sessions by combining 
 * transport layers, protocol serializers, realm URIs, and session authenticators.
 */
class WampSessionFactory
{
    /**
     * Create and return a new WampSession instance configured with the given dependencies.
     *
     * @param  \Hermod\LaravelWamp\Contracts\TransportContract  $transport  The active transport layer implementation.
     * @param  \Hermod\LaravelWamp\Contracts\SerializerContract  $serializer  The protocol serializer implementation.
     * @param  string  $realm  The WAMP realm URI to target.
     * @param  \Hermod\LaravelWamp\Contracts\AuthenticatorContract  $authenticator  The session authentication provider.
     * @return WampSession The newly instantiated WAMP session.
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