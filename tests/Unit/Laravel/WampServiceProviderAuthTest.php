<?php

use Hermod\LaravelWamp\Auth\AuthenticatorFactory;
use Hermod\LaravelWamp\Client\WampClientFactory;
use Hermod\LaravelWamp\Tests\TestCase;

uses(TestCase::class);

describe('WampServiceProvider — Auth', function () {

    it('registra AuthenticatorFactory nel container', function () {
        expect(app(AuthenticatorFactory::class))
            ->toBeInstanceOf(AuthenticatorFactory::class);
    });

    it('WampClientFactory riceve AuthenticatorFactory', function () {
        $factory = app(WampClientFactory::class);

        expect($factory)->toBeInstanceOf(WampClientFactory::class);
    });

    it('la configurazione default ha auth anonymous', function () {
        expect(config('wamp.connections.default.auth.method'))
            ->toBe('anonymous');
    });

    it('la configurazione default ha reconnect abilitato', function () {
        expect(config('wamp.connections.default.reconnect.enabled'))
            ->toBeTrue();
    });

    it('la configurazione default ha heartbeat abilitato', function () {
        expect(config('wamp.connections.default.heartbeat.enabled'))
            ->toBeTrue();
    });
});
