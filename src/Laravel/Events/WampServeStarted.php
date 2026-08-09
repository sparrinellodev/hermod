<?php

namespace Hermod\LaravelWamp\Laravel\Events;

use Hermod\LaravelWamp\Client\WampClient;

/**
 * Event dispatched when the WAMP serve command starts up.
 *
 * This event allows the Laravel application to hook into the worker initialization lifecycle 
 * (typically within a service provider) to register RPC procedures or set up subscriptions 
 * before the worker enters its main listening loop.
 */
class WampServeStarted
{
    /**
     * Create a new WampServeStarted event instance.
     *
     * @param  \Hermod\LaravelWamp\Client\WampClient  $client  The connected WAMP client instance used for registrations.
     */
    public function __construct(
        public readonly WampClient $client,
    ) {
    }
}