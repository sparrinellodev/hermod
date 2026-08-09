<?php

namespace Hermod\LaravelWamp\PubSub;

use Hermod\LaravelWamp\Exceptions\PubSubException;

/**
 * Registry managing active Pub/Sub subscriptions and pending asynchronous registration requests.
 *
 * Tracks subscription state transitions from initial request (SUBSCRIBE) to router confirmation 
 * (SUBSCRIBED), handles unsubscription lifecycles, and provides lookup utilities by topic or ID.
 */
class PendingSubscriptionRegistry
{
    /** @var array<int, array{topic: string, handler: callable}> requestId → pending */
    private array $pending = [];

    /** @var array<string, Subscription> topic → Subscription */
    private array $subscriptions = [];

    /** @var array<int, array{requestId: int, topic: string}> requestId → pending unsubscribe */
    private array $pendingUnsubscribes = [];

    // -------------------------------------------------------------------------
    // Pending Subscriptions
    // -------------------------------------------------------------------------

    /**
     * Register a new subscription request awaiting router confirmation.
     *
     * @param  int  $requestId  The unique request ID sent with the SUBSCRIBE message.
     * @param  string  $topic  The URI of the topic.
     * @param  callable  $handler  The callback handler executed when events are received.
     */
    public function registerPending(int $requestId, string $topic, callable $handler): void
    {
        $this->pending[$requestId] = [
            'topic' => $topic,
            'handler' => $handler,
        ];
    }

    /**
     * Confirm a pending subscription upon receiving a SUBSCRIBED message from the router.
     *
     * @param  int  $requestId  The request ID matching the confirmation.
     * @param  int  $subscriptionId  The unique subscription ID assigned by the WAMP router.
     * @return Subscription The newly established subscription instance.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\PubSubException If no matching pending subscription is found.
     */
    public function confirmSubscription(int $requestId, int $subscriptionId): Subscription
    {
        if (!isset($this->pending[$requestId])) {
            throw new PubSubException(
                "No pending subscription found for request ID: {$requestId}",
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
    // Pending Unsubscriptions
    // -------------------------------------------------------------------------

    /**
     * Register an unsubscription request awaiting router confirmation.
     *
     * @param  int  $requestId  The unique request ID sent with the UNSUBSCRIBE message.
     * @param  string  $topic  The URI of the topic being unsubscribed from.
     */
    public function registerPendingUnsubscribe(int $requestId, string $topic): void
    {
        $this->pendingUnsubscribes[$requestId] = ['topic' => $topic];
    }

    /**
     * Confirm a pending unsubscription upon receiving an UNSUBSCRIBED message from the router.
     *
     * @param  int  $requestId  The request ID matching the confirmation.
     */
    public function confirmUnsubscription(int $requestId): void
    {
        if (!isset($this->pendingUnsubscribes[$requestId])) {
            return;
        }

        $topic = $this->pendingUnsubscribes[$requestId]['topic'];
        unset($this->pendingUnsubscribes[$requestId]);
        unset($this->subscriptions[$topic]);
    }

    // -------------------------------------------------------------------------
    // Subscription Accessors & Lookups
    // -------------------------------------------------------------------------

    /**
     * Find an active subscription by its router-assigned subscription ID.
     *
     * @param  int  $subscriptionId  The WAMP subscription ID.
     * @return Subscription|null The matching subscription instance, or null if not found.
     */
    public function findBySubscriptionId(int $subscriptionId): ?Subscription
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->subscriptionId === $subscriptionId) {
                return $subscription;
            }
        }

        return null;
    }

    /**
     * Find an active subscription by its topic URI.
     *
     * @param  string  $topic  The topic URI.
     * @return Subscription|null The matching subscription instance, or null if not found.
     */
    public function findByTopic(string $topic): ?Subscription
    {
        return $this->subscriptions[$topic] ?? null;
    }

    /**
     * Check whether a topic is currently subscribed or has a pending subscription request.
     *
     * @param  string  $topic  The topic URI.
     * @return bool True if active or pending, false otherwise.
     */
    public function isTopicSubscribed(string $topic): bool
    {
        return isset($this->subscriptions[$topic])
            || $this->isTopicPending($topic);
    }

    /**
     * Get all active subscriptions mapped by topic.
     *
     * @return array<string, Subscription> Array of active subscriptions.
     */
    public function getAll(): array
    {
        return $this->subscriptions;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Check if a topic has an ongoing pending subscription request.
     *
     * @param  string  $topic  The topic URI.
     * @return bool True if pending, false otherwise.
     */
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