<?php

use Hermod\Client\WampClient;
use Hermod\Client\WampClientFactory;
use Hermod\Laravel\Facades\Wamp;
use Hermod\Serializer\SerializerFactory;
use Hermod\Tests\TestCase;

uses(TestCase::class);

describe('WampServiceProvider', function () {

    it('registra WampClientFactory nel container', function () {
        expect(app(WampClientFactory::class))
            ->toBeInstanceOf(WampClientFactory::class);
    });

    it('registra SerializerFactory nel container', function () {
        expect(app(SerializerFactory::class))
            ->toBeInstanceOf(SerializerFactory::class);
    });

    it('registra WampClient nel container', function () {
        expect(app(WampClient::class))
            ->toBeInstanceOf(WampClient::class);
    });

    it('registra l\'alias hermod.client', function () {
        expect(app('hermod.client'))
            ->toBeInstanceOf(WampClient::class);
    });

    it('la Facade risolve correttamente', function () {
        expect(Wamp::getFacadeRoot())
            ->toBeInstanceOf(WampClient::class);
    });

    it('carica la configurazione di default', function () {
        expect(config('hermod.default'))->toBe('default')
            ->and(config('hermod.connections.default.url'))->toBe('ws://localhost:8080/ws')
            ->and(config('hermod.connections.default.realm'))->toBe('realm1');
    });

    it('registra i serializzatori nella config', function () {
        expect(config('hermod.serializers'))
            ->toHaveKeys(['json', 'msgpack', 'cbor']);
    });
});
