<?php

use Hermod\LaravelWamp\Message\WampMessage;
use Hermod\LaravelWamp\PubSub\Publisher;
use Hermod\LaravelWamp\PubSub\Subscriber;
use Hermod\LaravelWamp\Rpc\Callee;
use Hermod\LaravelWamp\Rpc\Caller;
use Hermod\LaravelWamp\Rpc\MessageDispatcher;
use Hermod\LaravelWamp\Session\WampSession;

describe('MessageDispatcher — PubSub', function () {

    beforeEach(function () {
        $this->session = Mockery::mock(WampSession::class);
        $this->caller = Mockery::mock(Caller::class);
        $this->callee = Mockery::mock(Callee::class);
        $this->publisher = Mockery::mock(Publisher::class);
        $this->subscriber = Mockery::mock(Subscriber::class);

        $this->dispatcher = new MessageDispatcher(
            session: $this->session,
            caller: $this->caller,
            callee: $this->callee,
            publisher: $this->publisher,
            subscriber: $this->subscriber,
        );
    });

    afterEach(fn () => Mockery::close());

    it('smista PUBLISHED a Publisher::onPublished()', function () {
        $this->publisher
            ->shouldReceive('onPublished')
            ->once();

        $message = WampMessage::fromArray([17, 1, 9999]);
        $this->dispatcher->dispatch($message);
    });

    it('smista SUBSCRIBED a Subscriber::onSubscribed()', function () {
        $this->subscriber
            ->shouldReceive('onSubscribed')
            ->once();

        $message = WampMessage::fromArray([33, 1, 7777]);
        $this->dispatcher->dispatch($message);
    });

    it('smista UNSUBSCRIBED a Subscriber::onUnsubscribed()', function () {
        $this->subscriber
            ->shouldReceive('onUnsubscribed')
            ->once();

        $message = WampMessage::fromArray([35, 1]);
        $this->dispatcher->dispatch($message);
    });

    it('smista EVENT a Subscriber::onEvent()', function () {
        $this->subscriber
            ->shouldReceive('onEvent')
            ->once();

        $message = WampMessage::fromArray([36, 7777, 1111, [], ['dato']]);
        $this->dispatcher->dispatch($message);
    });

    it('smista ERROR su PUBLISH a Publisher::onError()', function () {
        $this->publisher
            ->shouldReceive('onError')
            ->once();

        // [8, PUBLISH(16), requestId, {}, error]
        $message = WampMessage::fromArray([8, 16, 1, [], 'wamp.error.not_authorized']);
        $this->dispatcher->dispatch($message);
    });

    it('non interferisce con lo smistamento RPC esistente', function () {
        $this->caller
            ->shouldReceive('onResult')
            ->once();

        $message = WampMessage::fromArray([50, 1, [], [42]]);
        $this->dispatcher->dispatch($message);
    });
});
