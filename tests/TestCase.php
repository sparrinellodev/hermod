<?php

namespace Hermod\Tests;

use Hermod\Laravel\WampServiceProvider;
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
            'url'        => 'ws://localhost:8080/ws',
            'realm'      => 'realm1',
            'serializer' => 'json',
        ]);
        $app['config']->set('hermod.serializers', [
            'json'    => \Hermod\Serializer\JsonSerializer::class,
            'msgpack' => \Hermod\Serializer\MsgpackSerializer::class,
            'cbor'    => \Hermod\Serializer\CborSerializer::class,
        ]);
    }
}
