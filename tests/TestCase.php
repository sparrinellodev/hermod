<?php

namespace Hermod\Tests;

use Hermod\Laravel\WampServiceProvider;
use Hermod\Serializer\CborSerializer;
use Hermod\Serializer\JsonSerializer;
use Hermod\Serializer\MsgpackSerializer;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            WampServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('hermod.default', 'default');
        $app['config']->set('hermod.connections.default', [
            'transport' => 'websocket',
            'url' => 'ws://localhost:8080/ws',
            'realm' => 'realm1',
            'serializer' => 'json',
            'auth' => [
                'method' => 'anonymous',
            ],
            'reconnect' => [
                'enabled' => true,
                'max_attempts' => 5,
                'base_delay' => 1.0,
                'max_delay' => 30.0,
                'multiplier' => 2.0,
            ],
            'heartbeat' => [
                'enabled' => true,
                'interval' => 30,
            ],
        ]);
        $app['config']->set('hermod.serializers', [
            'json' => JsonSerializer::class,
            'msgpack' => MsgpackSerializer::class,
            'cbor' => CborSerializer::class,
        ]);
    }
}
