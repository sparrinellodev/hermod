<?php

namespace Hermod\PubSub;

use Amp\DeferredFuture;
use Amp\Future;
use Hermod\Contracts\PublisherContract;
use Hermod\Exceptions\PubSubException;
use Hermod\Message\MessageFactory;
use Hermod\Message\WampMessage;
use Hermod\Rpc\RequestIdGenerator;
use Hermod\Session\WampSession;

class Publisher implements PublisherContract
{
    /** @var array<int, DeferredFuture> requestId → Deferred per publishWithAck */
    private array $pendingAcks = [];

    public function __construct(
        private readonly WampSession $session,
        private readonly RequestIdGenerator $idGenerator,
    ) {}

    // -------------------------------------------------------------------------
    // PublisherContract
    // -------------------------------------------------------------------------

    public function publish(string $topic, array $args = [], array $kwargs = []): void
    {
        $requestId = $this->idGenerator->generate();

        $this->session->send(
            MessageFactory::publish(
                requestId: $requestId,
                topic: $topic,
                args: $args,
                kwargs: $kwargs,
                acknowledge: false,
            ),
        );
    }

    public function publishWithAck(string $topic, array $args = [], array $kwargs = []): Future
    {
        $requestId = $this->idGenerator->generate();
        $deferred = new DeferredFuture;

        $this->pendingAcks[$requestId] = $deferred;

        $this->session->send(
            MessageFactory::publish(
                requestId: $requestId,
                topic: $topic,
                args: $args,
                kwargs: $kwargs,
                acknowledge: true,
            ),
        );

        return $deferred->getFuture();
    }

    // -------------------------------------------------------------------------
    // Gestione messaggi in ingresso
    // -------------------------------------------------------------------------

    /**
     * Chiamato dal MessageDispatcher quando arriva PUBLISHED.
     * [17, requestId, publicationId]
     */
    public function onPublished(WampMessage $message): void
    {
        $requestId = (int) $message->get(1);
        $publicationId = (int) $message->get(2);

        if (! isset($this->pendingAcks[$requestId])) {
            return;
        }

        $deferred = $this->pendingAcks[$requestId];
        unset($this->pendingAcks[$requestId]);

        $deferred->complete($publicationId);
    }

    /**
     * Chiamato dal MessageDispatcher quando arriva ERROR su PUBLISH.
     * [8, PUBLISH, requestId, details, error]
     */
    public function onError(WampMessage $message): void
    {
        $requestId = (int) $message->get(2);
        $wampError = (string) ($message->get(4) ?? 'wamp.error.unknown');

        if (! isset($this->pendingAcks[$requestId])) {
            return;
        }

        $deferred = $this->pendingAcks[$requestId];
        unset($this->pendingAcks[$requestId]);

        $deferred->error(new PubSubException(
            "Pubblicazione fallita su '{$wampError}'",
        ));
    }
}
