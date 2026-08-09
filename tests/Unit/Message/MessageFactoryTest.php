<?php

use Hermod\LaravelWamp\Message\MessageFactory;
use Hermod\LaravelWamp\Message\MessageType;

describe('MessageFactory', function () {

    describe('hello()', function () {

        it('costruisce un messaggio HELLO corretto', function () {
            $message = MessageFactory::hello('realm1');

            expect($message->type())->toBe(MessageType::HELLO)
                ->and($message->get(1))->toBe('realm1')
                ->and($message->get(2))->toHaveKey('roles')
                ->and($message->get(2)['roles'])->toHaveKeys(['caller', 'callee']);
        });
    });

    describe('goodbye()', function () {

        it('costruisce un messaggio GOODBYE con reason di default', function () {
            $message = MessageFactory::goodbye();

            expect($message->type())->toBe(MessageType::GOODBYE)
                ->and($message->get(2))->toBe('wamp.close.normal');
        });

        it('costruisce un messaggio GOODBYE con reason personalizzata', function () {
            $message = MessageFactory::goodbye('wamp.close.system_shutdown');

            expect($message->get(2))->toBe('wamp.close.system_shutdown');
        });
    });

    describe('call()', function () {

        it('costruisce un messaggio CALL corretto', function () {
            $message = MessageFactory::call(
                requestId: 42,
                procedure: 'com.myapp.somma',
                args: [3, 5],
            );

            expect($message->type())->toBe(MessageType::CALL)
                ->and($message->get(1))->toBe(42)
                ->and($message->get(2))->toBeObject()
                ->and((array) $message->get(2))->toBeEmpty()
                ->and($message->get(3))->toBe('com.myapp.somma')
                ->and($message->get(4))->toBe([3, 5]);
        });

        it('include kwargs quando forniti', function () {
            $message = MessageFactory::call(1, 'com.myapp.test', [], ['nome' => 'Mario']);

            expect($message->get(5))->toEqual((object) ['nome' => 'Mario']);
        });
    });

    describe('register()', function () {

        it('costruisce un messaggio REGISTER corretto', function () {
            $message = MessageFactory::register(99, 'com.myapp.somma');

            expect($message->type())->toBe(MessageType::REGISTER)
                ->and($message->get(1))->toBe(99)
                ->and($message->get(3))->toBe('com.myapp.somma');
        });
    });

    describe('yield()', function () {

        it('costruisce un messaggio YIELD corretto', function () {
            $message = MessageFactory::yield(55, [42]);

            expect($message->type())->toBe(MessageType::YIELD)
                ->and($message->get(1))->toBe(55)
                ->and($message->get(3))->toBe([42]);
        });
    });

    describe('yieldError()', function () {

        it('costruisce un messaggio ERROR corretto per INVOCATION', function () {
            $message = MessageFactory::yieldError(55, 'wamp.error.runtime_error', ['oops']);

            expect($message->type())->toBe(MessageType::ERROR)
                ->and($message->get(1))->toBe(MessageType::INVOCATION->value)
                ->and($message->get(2))->toBe(55)
                ->and($message->get(4))->toBe('wamp.error.runtime_error')
                ->and($message->get(5))->toBe(['oops']);
        });
    });
});
