<?php

use Hermod\Auth\AuthenticatorFactory;
use Hermod\Client\WampClientFactory;
use Hermod\Tests\TestCase;

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
        expect(config('hermod.connections.default.auth.method'))
            ->toBe('anonymous');
    });

    it('la configurazione default ha reconnect abilitato', function () {
        expect(config('hermod.connections.default.reconnect.enabled'))
            ->toBeTrue();
    });

    it('la configurazione default ha heartbeat abilitato', function () {
        expect(config('hermod.connections.default.heartbeat.enabled'))
            ->toBeTrue();
    });
});
