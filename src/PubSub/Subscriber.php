<?php

namespace Hermod\PubSub;

use Hermod\Contracts\SubscriberContract;
use Hermod\Exceptions\PubSubException;
use Hermod\Laravel\Events\WampEventReceived;
use Hermod\Message\MessageFactory;
use Hermod\Message\WampMessage;
use Hermod\Rpc\RequestIdGenerator;
use Hermod\Session\WampSession;
use Illuminate\Support\Facades\Event;

class Subscriber implements SubscriberContract
{
    public function __construct(
        private readonly WampSession $session,
        private readonly RequestIdGenerator $idGenerator,
        private readonly PendingSubscriptionRegistry $registry,
    ) {}

    // -------------------------------------------------------------------------
    // SubscriberContract
    // -------------------------------------------------------------------------

    public function subscribe(string $topic, callable $handler): Subscription
    {
        if ($this->registry->isTopicSubscribed($topic)) {
            throw new PubSubException(
                "Già sottoscritto al topic '{$topic}'.",
            );
        }

        $requestId = $this->idGenerator->generate();

        $this->registry->registerPending($requestId, $topic, $handler);

        $this->session->send(
            MessageFactory::subscribe($requestId, $topic),
        );

        // La Subscription vera viene creata in onSubscribed()
        // quando arriva la conferma dal router.
        // Restituiamo un placeholder che si completa dopo.
        return new Subscription(
            subscriptionId: 0,  // verrà aggiornato in onSubscribed
            topic: $topic,
            handler: $handler,
        );
    }

    public function unsubscribe(string $topic): void
    {
        $subscription = $this->registry->findByTopic($topic);

        if ($subscription === null) {
            throw new PubSubException(
                "Nessuna sottoscrizione attiva per il topic '{$topic}'.",
            );
        }

        $this->unsubscribeById($subscription);
    }

    public function unsubscribeById(Subscription $subscription): void
    {
        $requestId = $this->idGenerator->generate();

        $this->registry->registerPendingUnsubscribe($requestId, $subscription->topic);

        $this->session->send(
            MessageFactory::unsubscribe($requestId, $subscription->subscriptionId),
        );
    }

    public function getSubscriptions(): array
    {
        return $this->registry->getAll();
    }

    // -------------------------------------------------------------------------
    // Gestione messaggi in ingresso
    // -------------------------------------------------------------------------

    /**
     * Chiamato dal MessageDispatcher quando arriva SUBSCRIBED.
     * [33, requestId, subscriptionId]
     */
    public function onSubscribed(WampMessage $message): void
    {
        $requestId = (int) $message->get(1);
        $subscriptionId = (int) $message->get(2);

        try {
            $this->registry->confirmSubscription($requestId, $subscriptionId);
        } catch (PubSubException) {
            // requestId sconosciuto — ignoriamo
        }
    }

    /**
     * Chiamato dal MessageDispatcher quando arriva UNSUBSCRIBED.
     * [35, requestId]
     */
    public function onUnsubscribed(WampMessage $message): void
    {
        $requestId = (int) $message->get(1);
        $this->registry->confirmUnsubscription($requestId);
    }

    /**
     * Chiamato dal MessageDispatcher quando arriva EVENT.
     * [36, subscriptionId, publicationId, details, args?, kwargs?]
     */
    public function onEvent(WampMessage $message): void
    {
        $subscriptionId = (int) $message->get(1);
        $publicationId = (int) $message->get(2);
        $details = $message->get(3) ?? [];
        $args = $message->get(4) ?? [];
        $kwargs = $message->get(5) ?? [];

        $subscription = $this->registry->findBySubscriptionId($subscriptionId);

        if ($subscription === null) {
            return; // subscriptionId sconosciuto — ignoriamo
        }

        // 1. Eseguiamo l'handler registrato
        try {
            ($subscription->handler)(
                is_array($args) ? $args : [],
                is_array($kwargs) ? $kwargs : [],
                is_array($details) ? $details : [],
            );
        } catch (\Throwable) {
            // L'handler non deve bloccare il loop — ignoriamo eccezioni
        }

        // 2. Dispatchiamo anche un evento Laravel
        //    così l'app può reagire con listener senza registrare handler espliciti
        try {
            Event::dispatch(new WampEventReceived(
                topic: $subscription->topic,
                subscriptionId: $subscriptionId,
                publicationId: $publicationId,
                args: is_array($args) ? $args : [],
                kwargs: is_array($kwargs) ? $kwargs : [],
                details: is_array($details) ? $details : [],
            ));
        } catch (\Throwable) {
            // ignoriamo errori nel dispatch Laravel
        }
    }
}
