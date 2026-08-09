<?php

namespace Hermod\LaravelWamp\Client;

use Hermod\LaravelWamp\Auth\AuthenticatorFactory;
use Hermod\LaravelWamp\PubSub\PendingSubscriptionRegistry;
use Hermod\LaravelWamp\PubSub\Publisher;
use Hermod\LaravelWamp\PubSub\Subscriber;
use Hermod\LaravelWamp\Reconnect\ExponentialBackoffStrategy;
use Hermod\LaravelWamp\Reconnect\ReconnectManager;
use Hermod\LaravelWamp\Rpc\Callee;
use Hermod\LaravelWamp\Rpc\Caller;
use Hermod\LaravelWamp\Rpc\MessageDispatcher;
use Hermod\LaravelWamp\Rpc\PendingCallRegistry;
use Hermod\LaravelWamp\Rpc\RequestIdGenerator;
use Hermod\LaravelWamp\Serializer\SerializerFactory;
use Hermod\LaravelWamp\Session\WampSessionFactory;
use Hermod\LaravelWamp\Transport\TransportFactory;

/**
 * Factory class responsible for assembling and instantiating the WampClient.
 *
 * This class builds the entire dependency graph required by the WAMP client,
 * including serializers, transports, authentication, session management,
 * RPC/PubSub layers, and reconnection strategies.
 */
class WampClientFactory
{
    /**
     * Create a new WampClientFactory instance.
     *
     * @param SerializerFactory $serializerFactory
     * @param TransportFactory $transportFactory
     * @param WampSessionFactory $sessionFactory
     * @param AuthenticatorFactory $authenticatorFactory
     */
    public function __construct(
        private readonly SerializerFactory $serializerFactory,
        private readonly TransportFactory $transportFactory,
        private readonly WampSessionFactory $sessionFactory,
        private readonly AuthenticatorFactory $authenticatorFactory,
    ) {
    }

    /**
     * Build and configure a new WampClient instance.
     *
     * @param  array<string, mixed>  $config  The connection configuration array (from wamp.php).
     * @return \Hermod\LaravelWamp\Client\WampClient
     */
    public function make(array $config): WampClient
    {
        // 1. Resolve the serialization format (e.g., JSON, MsgPack, CBOR)
        $serializer = $this->serializerFactory->make(
            $config['serializer'] ?? 'json',
        );

        // 2. Initialize the transport layer (e.g., WebSockets)
        $transport = $this->transportFactory->make(
            type: $config['transport'] ?? 'websocket',
            url: $config['url'],
            serializer: $serializer,
        );

        // 3. Resolve the authentication method (Anonymous, Ticket, WAMP-CRA)
        $authenticator = $this->authenticatorFactory->make(
            $config['auth'] ?? ['method' => 'anonymous'],
        );

        // 4. Create the WAMP session manager
        $session = $this->sessionFactory->make(
            transport: $transport,
            serializer: $serializer,
            realm: $config['realm'],
            authenticator: $authenticator,
        );

        // 5. Bootstrap the RPC (Remote Procedure Call) layer
        $idGenerator = new RequestIdGenerator;
        $registry = new PendingCallRegistry($idGenerator);
        $caller = new Caller($session, $registry);
        $callee = new Callee($session, $idGenerator);

        // 6. Bootstrap the Pub/Sub (Publish & Subscribe) layer
        $subRegistry = new PendingSubscriptionRegistry;
        $publisher = new Publisher($session, $idGenerator);
        $subscriber = new Subscriber($session, $idGenerator, $subRegistry);

        // 7. Setup the message dispatcher to route incoming messages
        $dispatcher = new MessageDispatcher(
            session: $session,
            caller: $caller,
            callee: $callee,
            publisher: $publisher,
            subscriber: $subscriber,
        );

        // 8. Configure the automatic reconnection strategy (Exponential Backoff)
        $reconnectConfig = $config['reconnect'] ?? [];
        $strategy = new ExponentialBackoffStrategy(
            maxAttempts: (int) ($reconnectConfig['max_attempts'] ?? 5),
            baseDelay: (float) ($reconnectConfig['base_delay'] ?? 1.0),
            maxDelay: (float) ($reconnectConfig['max_delay'] ?? 30.0),
            multiplier: (float) ($reconnectConfig['multiplier'] ?? 2.0),
        );

        $reconnectManager = new ReconnectManager(
            strategy: $strategy,
            enabled: (bool) ($reconnectConfig['enabled'] ?? true),
        );

        // 9. Assemble and return the final WampClient instance
        return new WampClient(
            session: $session,
            caller: $caller,
            callee: $callee,
            publisher: $publisher,
            subscriber: $subscriber,
            dispatcher: $dispatcher,
            reconnectManager: $reconnectManager,
        );
    }
}