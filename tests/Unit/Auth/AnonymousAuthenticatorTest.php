<?php

use Hermod\Auth\AnonymousAuthenticator;
use Hermod\Auth\AuthMethod;

describe('AnonymousAuthenticator', function () {

    beforeEach(function () {
        $this->auth = new AnonymousAuthenticator;
    });

    it('restituisce il metodo corretto', function () {
        expect($this->auth->method())->toBe(AuthMethod::Anonymous);
    });

    it('non ha authId', function () {
        expect($this->auth->authId())->toBeNull();
    });

    it('non ha authExtra', function () {
        expect($this->auth->authExtra())->toBe([]);
    });

    it('non richiede challenge', function () {
        expect($this->auth->requiresChallenge())->toBeFalse();
    });

    it('restituisce null per handleChallenge', function () {
        expect($this->auth->handleChallenge('qualsiasi challenge'))->toBeNull();
    });
});
