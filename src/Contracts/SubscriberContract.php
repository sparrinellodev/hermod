<?php

namespace Hermod\LaravelWamp\Contracts;

use Hermod\LaravelWamp\PubSub\Subscription;

/**
 * Defines the contract for managing topic subscriptions (Pub/Sub pattern).
 *
 * A Subscriber allows the client to listen to events published on specific 
 * topics and handles incoming event notifications.
 */
interface SubscriberContract
{
    /**
     * Subscribe to a topic and register a handler for incoming events.
     *
     * @param  string  $topic  The URI of the topic (e.g., 'com.myapp.notifications').
     * @param  callable  $handler  The function invoked whenever an event is received.
     * Signature: function(array $args, array $kwargs, array $details): void
     * @return Subscription An object representing the active subscription.
     */
    public function subscribe(string $topic, callable $handler): Subscription;

    /**
     * Unsubscribe from a topic using its URI.
     *
     * @param  string  $topic  The URI of the topic to remove subscriptions for.
     */
    public function unsubscribe(string $topic): void;

    /**
     * Unsubscribe using a specific Subscription instance.
     *
     * @param  Subscription  $subscription  The subscription object to terminate.
     */
    public function unsubscribeById(Subscription $subscription): void;

    /**
     * Get all currently active topic subscriptions.
     *
     * @return array<string, Subscription> An associative array mapping topic URIs to their subscriptions.
     */
    public function getSubscriptions(): array;
}