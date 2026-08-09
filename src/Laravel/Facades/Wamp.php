<?php

namespace Hermod\LaravelWamp\Laravel\Facades;

use Amp\Future;
use Hermod\LaravelWamp\Client\WampClient;
use Hermod\LaravelWamp\PubSub\Subscription;
use Illuminate\Support\Facades\Facade;

/**
 * Laravel Facade for the WAMP client service.
 *
 * Provides static access to underlying WampClient operations such as RPC calls, 
 * procedure registrations, Pub/Sub publishing, and subscriptions.
 *
 * @method static void connect()
 * @method static void disconnect()
 * @method static bool isConnected()
 * @method static mixed call(string $procedure, array $args = [], array $kwargs = [])
 * @method static Future callAsync(string $procedure, array $args = [], array $kwargs = [])
 * @method static void register(string $procedure, callable $handler)
 * @method static void unregister(string $procedure)
 * @method static array getRegistrations()
 * @method static void publish(string $topic, array $args = [], array $kwargs = [])
 * @method static Future publishWithAck(string $topic, array $args = [], array $kwargs = [])
 * @method static Subscription subscribe(string $topic, callable $handler)
 * @method static void unsubscribe(string $topic)
 * @method static void unsubscribeById(Subscription $subscription)
 * @method static array getSubscriptions()
 * @method static void listen()
 * @method static void tick()
 * @method static ?int getSessionId()
 * @method static string getRealm()
 *
 * @see \Hermod\LaravelWamp\Client\WampClient
 */
class Wamp extends Facade
{
    /**
     * Get the registered name of the component in the container.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'wamp.client';
    }
}