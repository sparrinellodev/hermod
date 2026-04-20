<?php

use Hermod\Message\MessageFactory;
use Hermod\Message\MessageType;

describe('MessageFactory — PubSub', function () {

    // -------------------------------------------------------------------------
    // publish()
    // -------------------------------------------------------------------------

    describe('publish()', function () {

        it('costruisce un messaggio PUBLISH senza acknowledge', function () {
            $message = MessageFactory::publish(
                requestId: 1,
                topic: 'com.myapp.notifiche',
                args: ['ciao'],
            );

            expect($message->type())->toBe(MessageType::PUBLISH)
                ->and($message->get(1))->toBe(1)
                ->and($message->get(3))->toBe('com.myapp.notifiche')
                ->and($message->get(4))->toBe(['ciao']);

            // options non deve avere acknowledge
            $options = $message->get(2);
            expect(isset($options->acknowledge))->toBeFalse();
        });

        it('costruisce un messaggio PUBLISH con acknowledge=true', function () {
            $message = MessageFactory::publish(
                requestId: 1,
                topic: 'com.myapp.notifiche',
                args: [],
                kwargs: [],
                acknowledge: true,
            );

            expect($message->get(2))->toBe(['acknowledge' => true]);
        });

        it('include kwargs nel messaggio', function () {
            $message = MessageFactory::publish(
                requestId: 1,
                topic: 'com.myapp.test',
                args: [],
                kwargs: ['nome' => 'Mario'],
            );

            // kwargs come stdClass quando non vuoto
            expect($message->get(5))->not->toBeEmpty();
        });
    });

    // -------------------------------------------------------------------------
    // subscribe()
    // -------------------------------------------------------------------------

    describe('subscribe()', function () {

        it('costruisce un messaggio SUBSCRIBE corretto', function () {
            $message = MessageFactory::subscribe(42, 'com.myapp.notifiche');

            expect($message->type())->toBe(MessageType::SUBSCRIBE)
                ->and($message->get(1))->toBe(42)
                ->and($message->get(3))->toBe('com.myapp.notifiche');
        });

        it('include options come oggetto vuoto', function () {
            $message = MessageFactory::subscribe(1, 'com.myapp.test');
            $raw = json_encode($message->toArray());

            // options deve essere {} non []
            expect($raw)->toContain('{}');
        });
    });

    // -------------------------------------------------------------------------
    // unsubscribe()
    // -------------------------------------------------------------------------

    describe('unsubscribe()', function () {

        it('costruisce un messaggio UNSUBSCRIBE corretto', function () {
            $message = MessageFactory::unsubscribe(99, 7777);

            expect($message->type())->toBe(MessageType::UNSUBSCRIBE)
                ->and($message->get(1))->toBe(99)
                ->and($message->get(2))->toBe(7777);
        });
    });
});
