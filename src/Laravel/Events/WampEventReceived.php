<?php

namespace Hermod\Laravel\Events;

class WampEventReceived
{
    public function __construct(
        public readonly string $topic,
        public readonly int $subscriptionId,
        public readonly int $publicationId,
        public readonly array $args,
        public readonly array $kwargs,
        public readonly array $details,
    ) {}
}
