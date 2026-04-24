<?php

use Hermod\Auth\AuthMethod;

describe('AuthMethod', function () {

    it('ha i valori corretti per ogni metodo', function () {
        expect(AuthMethod::Anonymous->value)->toBe('anonymous')
            ->and(AuthMethod::Ticket->value)->toBe('ticket')
            ->and(AuthMethod::WampCra->value)->toBe('wampcra');
    });

    it('risolve correttamente da stringa valida', function () {
        expect(AuthMethod::tryFrom('anonymous'))->toBe(AuthMethod::Anonymous)
            ->and(AuthMethod::tryFrom('ticket'))->toBe(AuthMethod::Ticket)
            ->and(AuthMethod::tryFrom('wampcra'))->toBe(AuthMethod::WampCra);
    });

    it('restituisce null per stringa sconosciuta', function () {
        expect(AuthMethod::tryFrom('oauth2'))->toBeNull()
            ->and(AuthMethod::tryFrom(''))->toBeNull();
    });
});
