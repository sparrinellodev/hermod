<?php

use Hermod\Auth\AuthMethod;
use Hermod\Auth\TicketAuthenticator;

describe('TicketAuthenticator', function () {

    beforeEach(function () {
        $this->auth = new TicketAuthenticator(
            authId: 'user123',
            ticket: 'my-secret-ticket',
        );
    });

    it('restituisce il metodo corretto', function () {
        expect($this->auth->method())->toBe(AuthMethod::Ticket);
    });

    it('restituisce l\'authId corretto', function () {
        expect($this->auth->authId())->toBe('user123');
    });

    it('non ha authExtra', function () {
        expect($this->auth->authExtra())->toBe([]);
    });

    it('richiede challenge', function () {
        expect($this->auth->requiresChallenge())->toBeTrue();
    });

    it('restituisce il ticket come risposta alla challenge', function () {
        // Per Ticket auth la challenge del router viene ignorata
        // e il ticket viene inviato direttamente come firma
        $result = $this->auth->handleChallenge('challenge-ignorata');

        expect($result)->toBe('my-secret-ticket');
    });

    it('restituisce sempre il ticket indipendentemente dalla challenge', function () {
        expect($this->auth->handleChallenge('challenge-1'))
            ->toBe('my-secret-ticket')
            ->and($this->auth->handleChallenge('challenge-2'))
            ->toBe('my-secret-ticket');
    });
});
