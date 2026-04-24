<?php

use Hermod\Message\MessageFactory;
use Hermod\Message\MessageType;

describe('MessageFactory — Auth', function () {

    // -------------------------------------------------------------------------
    // helloWithAuth()
    // -------------------------------------------------------------------------

    describe('helloWithAuth()', function () {

        it('costruisce HELLO con anonymous auth', function () {
            $message = MessageFactory::helloWithAuth(
                realm: 'realm1',
                authMethod: 'anonymous',
            );

            expect($message->type())->toBe(MessageType::HELLO)
                ->and($message->get(1))->toBe('realm1')
                ->and($message->get(2)['authmethods'])->toBe(['anonymous']);
        });

        it('include authid quando fornito', function () {
            $message = MessageFactory::helloWithAuth(
                realm: 'realm1',
                authMethod: 'ticket',
                authId: 'user123',
            );

            expect($message->get(2)['authid'])->toBe('user123');
        });

        it('non include authid quando null', function () {
            $message = MessageFactory::helloWithAuth(
                realm: 'realm1',
                authMethod: 'anonymous',
                authId: null,
            );

            expect(isset($message->get(2)['authid']))->toBeFalse();
        });

        it('include authextra quando fornito', function () {
            $message = MessageFactory::helloWithAuth(
                realm: 'realm1',
                authMethod: 'wampcra',
                authId: 'user123',
                authExtra: ['channel_binding' => null],
            );

            expect($message->get(2)['authextra'])->toBe(['channel_binding' => null]);
        });

        it('include authextra come oggetto vuoto quando non fornito', function () {
            $message = MessageFactory::helloWithAuth(
                realm: 'realm1',
                authMethod: 'anonymous',
            );

            $raw = json_encode($message->toArray());
            expect($raw)->toContain('"authextra":{}');
        });

        it('include sempre i roles', function () {
            $message = MessageFactory::helloWithAuth(
                realm: 'realm1',
                authMethod: 'anonymous',
            );

            expect($message->get(2)['roles'])
                ->toHaveKeys(['caller', 'callee']);
        });
    });

    // -------------------------------------------------------------------------
    // authenticate()
    // -------------------------------------------------------------------------

    describe('authenticate()', function () {

        it('costruisce un messaggio AUTHENTICATE corretto', function () {
            $message = MessageFactory::authenticate('my-signature');

            expect($message->type())->toBe(MessageType::AUTHENTICATE)
                ->and($message->get(1))->toBe('my-signature');
        });

        it('include extra come oggetto vuoto quando non fornito', function () {
            $message = MessageFactory::authenticate('sig');
            $raw = json_encode($message->toArray());

            expect($raw)->toContain('{}');
        });

        it('include extra quando fornito', function () {
            $message = MessageFactory::authenticate('sig', ['channel_binding' => 'tls-unique']);

            expect($message->get(2))->toBe(['channel_binding' => 'tls-unique']);
        });
    });
});
