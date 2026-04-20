<?php

namespace Hermod\PubSub;

class Subscription
{
    public readonly mixed $handler;

    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $topic,
        callable $handler,
    ) {
        $this->handler = $handler;
    }
}
