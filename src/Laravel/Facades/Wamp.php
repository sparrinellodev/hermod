<?php

namespace Hermod\Laravel\Facades;

use Amp\Future;
use Hermod\Client\WampClient;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void    connect()
 * @method static void    disconnect()
 * @method static bool    isConnected()
 * @method static mixed   call(string $procedure, array $args = [], array $kwargs = [])
 * @method static Future  callAsync(string $procedure, array $args = [], array $kwargs = [])
 * @method static void    register(string $procedure, callable $handler)
 * @method static void    unregister(string $procedure)
 * @method static array   getRegistrations()
 * @method static void    listen()
 * @method static void    tick()
 * @method static ?int    getSessionId()
 * @method static string  getRealm()
 *
 * @see WampClient
 */
class Wamp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'hermod.client';
    }
}
