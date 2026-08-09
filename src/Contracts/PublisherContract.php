<?php

namespace Hermod\LaravelWamp\Contracts;

use Amp\Future;

/**
 * Defines the contract for publishing events to a topic (Pub/Sub pattern).
 *
 * A Publisher can send events either via a fire-and-forget approach 
 * or with acknowledgment tracking via a Future.
 */
interface PublisherContract
{
    /**
     * Publish an event to a topic without waiting for confirmation.
     * * This uses a "fire-and-forget" approach, meaning the router will not 
     * respond with a PUBLISHED message.
     *
     * @param  string  $topic  The URI of the topic (e.g., 'com.myapp.notifications').
     * @param  array<mixed>  $args   Positional arguments for the event.
     * @param  array<string, mixed>  $kwargs  Keyword arguments for the event.
     */
    public function publish(string $topic, array $args = [], array $kwargs = []): void;

    /**
     * Publish an event to a topic and wait for the PUBLISHED acknowledgment from the router.
     * * This requires the router to support the acknowledgment option.
     *
     * @param  string  $topic  The URI of the topic (e.g., 'com.myapp.notifications').
     * @param  array<mixed>  $args   Positional arguments for the event.
     * @param  array<string, mixed>  $kwargs  Keyword arguments for the event.
     * @return Future<int> A future that resolves to the publication ID assigned by the router.
     */
    public function publishWithAck(string $topic, array $args = [], array $kwargs = []): Future;
}