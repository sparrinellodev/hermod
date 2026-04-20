<?php

namespace Hermod\PubSub;

use Hermod\Exceptions\PubSubException;

class PendingSubscriptionRegistry
{
    /** @var array<int, array{topic: string, handler: callable}> requestId → pending */
    private array $pending = [];

    /** @var array<string, Subscription> topic → Subscription */
    private array $subscriptions = [];

    /** @var array<int, array{requestId: int, topic: string}> requestId → pending unsubscribe */
    private array $pendingUnsubscribes = [];

    // -------------------------------------------------------------------------
    // Sottoscrizioni in attesa
    // -------------------------------------------------------------------------

    public function registerPending(int $requestId, string $topic, callable $handler): void
    {
        $this->pending[$requestId] = [
            'topic' => $topic,
            'handler' => $handler,
        ];
    }

    public function confirmSubscription(int $requestId, int $subscriptionId): Subscription
    {
        if (! isset($this->pending[$requestId])) {
            throw new PubSubException(
                "Nessuna sottoscrizione pendente per requestId: {$requestId}",
            );
        }

        ['topic' => $topic, 'handler' => $handler] = $this->pending[$requestId];
        unset($this->pending[$requestId]);

        $subscription = new Subscription(
            subscriptionId: $subscriptionId,
            topic: $topic,
            handler: $handler,
        );

        $this->subscriptions[$topic] = $subscription;

        return $subscription;
    }

    // -------------------------------------------------------------------------
    // Disiscrizioni in attesa
    // -------------------------------------------------------------------------

    public function registerPendingUnsubscribe(int $requestId, string $topic): void
    {
        $this->pendingUnsubscribes[$requestId] = ['topic' => $topic];
    }

    public function confirmUnsubscription(int $requestId): void
    {
        if (! isset($this->pendingUnsubscribes[$requestId])) {
            return;
        }

        $topic = $this->pendingUnsubscribes[$requestId]['topic'];
        unset($this->pendingUnsubscribes[$requestId]);
        unset($this->subscriptions[$topic]);
    }

    // -------------------------------------------------------------------------
    // Accesso alle sottoscrizioni
    // -------------------------------------------------------------------------

    public function findBySubscriptionId(int $subscriptionId): ?Subscription
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->subscriptionId === $subscriptionId) {
                return $subscription;
            }
        }

        return null;
    }

    public function findByTopic(string $topic): ?Subscription
    {
        return $this->subscriptions[$topic] ?? null;
    }

    public function isTopicSubscribed(string $topic): bool
    {
        return isset($this->subscriptions[$topic])
            || $this->isTopicPending($topic);
    }

    public function getAll(): array
    {
        return $this->subscriptions;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function isTopicPending(string $topic): bool
    {
        foreach ($this->pending as $pending) {
            if ($pending['topic'] === $topic) {
                return true;
            }
        }

        return false;
    }
}
