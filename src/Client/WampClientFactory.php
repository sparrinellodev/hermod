<?php

namespace Hermod\Client;

use Hermod\PubSub\PendingSubscriptionRegistry;
use Hermod\PubSub\Publisher;
use Hermod\PubSub\Subscriber;
use Hermod\Rpc\Callee;
use Hermod\Rpc\Caller;
use Hermod\Rpc\MessageDispatcher;
use Hermod\Rpc\PendingCallRegistry;
use Hermod\Rpc\RequestIdGenerator;
use Hermod\Serializer\SerializerFactory;
use Hermod\Session\WampSessionFactory;
use Hermod\Transport\WebSocketTransportFactory;

class WampClientFactory
{
    public function __construct(
        private readonly SerializerFactory $serializerFactory,
        private readonly WebSocketTransportFactory $transportFactory,
        private readonly WampSessionFactory $sessionFactory,
    ) {}

    /** @param array<mixed> $config */
    public function make(array $config): WampClient
    {
        // 1. Serializer
        $serializer = $this->serializerFactory->make(
            $config['serializer'] ?? 'json',
        );

        // 2. Transport
        $transport = $this->transportFactory->make(
            url: $config['url'],
            serializer: $serializer,
        );

        // 3. Session
        $session = $this->sessionFactory->make(
            transport: $transport,
            serializer: $serializer,
            realm: $config['realm'],
        );

        // 4. RPC Layer
        $idGenerator = new RequestIdGenerator;
        $registry = new PendingCallRegistry($idGenerator);
        $caller = new Caller($session, $registry);
        $callee = new Callee($session, $idGenerator);

        // 5. PubSub Layer
        $subRegistry = new PendingSubscriptionRegistry;
        $publisher = new Publisher($session, $idGenerator);
        $subscriber = new Subscriber($session, $idGenerator, $subRegistry);

        // 6. Dispatcher
        $dispatcher = new MessageDispatcher(
            session: $session,
            caller: $caller,
            callee: $callee,
            publisher: $publisher,
            subscriber: $subscriber,
        );

        // 7. Client
        return new WampClient(
            session: $session,
            caller: $caller,
            callee: $callee,
            publisher: $publisher,
            subscriber: $subscriber,
            dispatcher: $dispatcher,
        );
    }
}
