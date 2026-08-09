<?php

use Hermod\LaravelWamp\Client\WampClient;
use Hermod\LaravelWamp\Client\WampClientFactory;
use Hermod\LaravelWamp\Laravel\Facades\Wamp;
use Hermod\LaravelWamp\Serializer\SerializerFactory;
use Hermod\LaravelWamp\Tests\TestCase;

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

    it('registra l\'alias wamp.client', function () {
        expect(app('wamp.client'))
            ->toBeInstanceOf(WampClient::class);
    });

    it('la Facade risolve correttamente', function () {
        expect(Wamp::getFacadeRoot())
            ->toBeInstanceOf(WampClient::class);
    });

    it('carica la configurazione di default', function () {
        expect(config('wamp.default'))->toBe('default')
            ->and(config('wamp.connections.default.url'))->toBe('ws://localhost:8080/ws')
            ->and(config('wamp.connections.default.realm'))->toBe('realm1');
    });

    it('registra i serializzatori nella config', function () {
        expect(config('wamp.serializers'))
            ->toHaveKeys(['json', 'msgpack', 'cbor']);
    });
});
