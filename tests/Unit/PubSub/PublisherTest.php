<?php

use Amp\Future;
use Hermod\Exceptions\PubSubException;
use Hermod\Message\MessageType;
use Hermod\Message\WampMessage;
use Hermod\PubSub\Publisher;
use Hermod\Rpc\RequestIdGenerator;
use Hermod\Session\WampSession;

describe('Publisher', function () {

    beforeEach(function () {
        $this->session = Mockery::mock(WampSession::class);
        $this->publisher = new Publisher($this->session, new RequestIdGenerator);
    });

    afterEach(fn () => Mockery::close());

    // -------------------------------------------------------------------------
    // publish()
    // -------------------------------------------------------------------------

    describe('publish()', function () {

        it('invia PUBLISH senza opzione acknowledge', function () {
            $this->session
                ->shouldReceive('send')
                ->once()
                ->withArgs(function (WampMessage $message) {
                    $options = $message->get(2);

                    return ! property_exists($options, 'acknowledge');
                });

            $this->publisher->publish('com.myapp.test', []);
        });

        it('invia un messaggio PUBLISH senza acknowledge', function () {
            $this->session
                ->shouldReceive('send')
                ->once()
                ->withArgs(function (WampMessage $message) {
                    return $message->type() === MessageType::PUBLISH
                        && $message->get(3) === 'com.myapp.notifiche'
                        && $message->get(4) === []
                        && $message->get(5)->dato === 'valore';
                });

            $this->publisher->publish('com.myapp.notifiche', ['dato' => 'valore']);
        });

        it('invia PUBLISH con args e kwargs', function () {
            $this->session
                ->shouldReceive('send')
                ->once()
                ->withArgs(function (WampMessage $message) {
                    return $message->get(4) === [1, 2, 3];
                });

            $this->publisher->publish('com.myapp.test', [1, 2, 3]);
        });
    });

    // -------------------------------------------------------------------------
    // publishWithAck()
    // -------------------------------------------------------------------------

    describe('publishWithAck()', function () {

        it('restituisce un Future', function () {
            $this->session->shouldReceive('send')->once();

            $future = $this->publisher->publishWithAck('com.myapp.test', []);

            expect($future)->toBeInstanceOf(Future::class);
        });

        it('invia PUBLISH con opzione acknowledge=true', function () {
            $this->session
                ->shouldReceive('send')
                ->once()
                ->withArgs(function (WampMessage $message) {
                    $options = $message->get(2);

                    return ($options->acknowledge ?? false) === true;
                });

            $this->publisher->publishWithAck('com.myapp.test', []);
        });

        it('risolve il Future quando arriva PUBLISHED', function () {
            $this->session->shouldReceive('send')->once();

            $future = $this->publisher->publishWithAck('com.myapp.test', []);

            // Recuperiamo il requestId dal messaggio inviato
            $requestId = null;
            $this->session
                ->shouldReceive('send')
                ->never(); // non vogliamo un secondo send

            // Simuliamo PUBLISHED dal router
            // [17, requestId, publicationId]
            // Prima dobbiamo trovare il requestId
            $pendingAcks = (new ReflectionProperty($this->publisher, 'pendingAcks'))
                ->getValue($this->publisher);

            $requestId = array_key_first($pendingAcks);

            $published = WampMessage::fromArray([17, $requestId, 5555]);
            $this->publisher->onPublished($published);

            expect($future->await())->toBe(5555);
        });

        it('rigetta il Future quando arriva ERROR su PUBLISH', function () {
            $this->session->shouldReceive('send')->once();

            $future = $this->publisher->publishWithAck('com.myapp.test', []);

            $pendingAcks = (new ReflectionProperty($this->publisher, 'pendingAcks'))
                ->getValue($this->publisher);

            $requestId = array_key_first($pendingAcks);

            // [8, PUBLISH, requestId, {}, "wamp.error.not_authorized"]
            $error = WampMessage::fromArray([8, 16, $requestId, [], 'wamp.error.not_authorized']);
            $this->publisher->onError($error);

            expect(fn () => $future->await())
                ->toThrow(PubSubException::class);
        });

        it('ignora PUBLISHED per requestId sconosciuto', function () {
            $unknown = WampMessage::fromArray([17, 99999, 1234]);
            $this->publisher->onPublished($unknown);

            expect(true)->toBeTrue();
        });
    });
});
