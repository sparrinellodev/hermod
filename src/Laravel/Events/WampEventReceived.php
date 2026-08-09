<?php

namespace Hermod\LaravelWamp\Laravel\Events;

/**
 * Event dispatched when a WAMP publication event is received by a subscription handler.
 *
 * This event wraps the incoming publication details—including the topic URI, 
 * subscription and publication IDs, positional arguments, keyword arguments, 
 * and supplementary metadata details.
 */
class WampEventReceived
{
    /**
     * Create a new WampEventReceived event instance.
     *
     * @param  string  $topic  The URI of the topic the event was published to.
     * @param  int  $subscriptionId  The unique subscription ID that matched this event.
     * @param  int  $publicationId  The unique publication ID assigned by the router.
     * @param  array<mixed>  $args  Positional arguments sent with the event.
     * @param  array<string, mixed>  $kwargs  Keyword arguments sent with the event.
     * @param  array<string, mixed>  $details  Additional metadata details about the publication.
     */
    public function __construct(
        public readonly string $topic,
        public readonly int $subscriptionId,
        public readonly int $publicationId,
        public readonly array $args,
        public readonly array $kwargs,
        public readonly array $details,
    ) {
    }
}