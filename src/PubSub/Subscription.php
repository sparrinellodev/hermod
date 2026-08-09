<?php

namespace Hermod\LaravelWamp\PubSub;

/**
 * Value object representing an active WAMP topic subscription.
 *
 * Encapsulates the unique router-assigned subscription ID, the topic URI, 
 * and the callback handler associated with incoming publication events.
 */
class Subscription
{
    /** @var callable The callback handler invoked when an event arrives on the topic. */
    public readonly mixed $handler;

    /**
     * Create a new Subscription instance.
     *
     * @param  int  $subscriptionId  The unique subscription ID assigned by the WAMP router.
     * @param  string  $topic  The URI of the subscribed topic.
     * @param  callable  $handler  The callback invoked upon event reception.
     */
    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $topic,
        callable $handler,
    ) {
        $this->handler = $handler;
    }
}