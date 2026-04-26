<?php

namespace Hermod\Client;

use Hermod\Auth\AuthenticatorFactory;
use Hermod\PubSub\PendingSubscriptionRegistry;
use Hermod\PubSub\Publisher;
use Hermod\PubSub\Subscriber;
use Hermod\Reconnect\ExponentialBackoffStrategy;
use Hermod\Reconnect\ReconnectManager;
use Hermod\Rpc\Callee;
use Hermod\Rpc\Caller;
use Hermod\Rpc\MessageDispatcher;
use Hermod\Rpc\PendingCallRegistry;
use Hermod\Rpc\RequestIdGenerator;
use Hermod\Serializer\SerializerFactory;
use Hermod\Session\WampSessionFactory;
use Hermod\Transport\TransportFactory;

class WampClientFactory
{
    /**
     * Summary of __construct
     */
    public function __construct(
        private readonly SerializerFactory $serializerFactory,
        private readonly TransportFactory $transportFactory,
        private readonly WampSessionFactory $sessionFactory,
        private readonly AuthenticatorFactory $authenticatorFactory,
    ) {}

    /**
     * Summary of make
     *
     * @param  array<mixed>  $config
     */
    public function make(array $config): WampClient
    {
        // 1. Serializer
        $serializer = $this->serializerFactory->make(
            $config['serializer'] ?? 'json',
        );

        // 2. Transport
        $transport = $this->transportFactory->make(
            type: $config['transport'] ?? 'websocket',
            url: $config['url'],
            serializer: $serializer,
        );

        // 3. Authenticator
        $authenticator = $this->authenticatorFactory->make(
            $config['auth'] ?? ['method' => 'anonymous'],
        );

        // 4. Session
        $session = $this->sessionFactory->make(
            transport: $transport,
            serializer: $serializer,
            realm: $config['realm'],
            authenticator: $authenticator,
        );

        // 5. RPC Layer
        $idGenerator = new RequestIdGenerator;
        $registry = new PendingCallRegistry($idGenerator);
        $caller = new Caller($session, $registry);
        $callee = new Callee($session, $idGenerator);

        // 6. PubSub Layer
        $subRegistry = new PendingSubscriptionRegistry;
        $publisher = new Publisher($session, $idGenerator);
        $subscriber = new Subscriber($session, $idGenerator, $subRegistry);

        // 7. Dispatcher
        $dispatcher = new MessageDispatcher(
            session: $session,
            caller: $caller,
            callee: $callee,
            publisher: $publisher,
            subscriber: $subscriber,
        );

        // 8. Reconnect
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

        // 9. Client
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
