<?php

namespace Hermod\Laravel\Events;

use Hermod\Client\WampClient;

class WampServeStarted
{
    public function __construct(
        public readonly WampClient $client,
    ) {}
}
