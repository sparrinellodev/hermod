<?php

use Amp\Future;
use Hermod\LaravelWamp\Exceptions\RpcException;
use Hermod\LaravelWamp\Message\MessageType;
use Hermod\LaravelWamp\Message\WampMessage;
use Hermod\LaravelWamp\Rpc\Caller;
use Hermod\LaravelWamp\Rpc\PendingCallRegistry;
use Hermod\LaravelWamp\Rpc\RequestIdGenerator;
use Hermod\LaravelWamp\Session\WampSession;

describe('Caller', function () {

    beforeEach(function () {
        $this->session = Mockery::mock(WampSession::class);
        $this->registry = new PendingCallRegistry(new RequestIdGenerator);
        $this->caller = new Caller($this->session, $this->registry);
    });

    afterEach(fn () => Mockery::close());

    it('invia un messaggio CALL alla sessione', function () {
        $this->session
            ->shouldReceive('send')
            ->once()
            ->withArgs(function (WampMessage $message) {
                return $message->type() === MessageType::CALL
                    && $message->get(3) === 'com.myapp.test';
            });

        $future = $this->caller->callAsync('com.myapp.test', [1, 2]);

        expect($future)->toBeInstanceOf(Future::class);
    });

    it('risolve il Future quando arriva RESULT', function () {
        $this->session->shouldReceive('send')->once();

        $future = $this->caller->callAsync('com.myapp.test', [3, 5]);

        // Recuperiamo il requestId dalla registry
        $pending = collect(
            (new ReflectionProperty($this->registry, 'pending'))
                ->getValue($this->registry),
        )->first();

        // Simuliamo RESULT dal router
        // [50, requestId, {}, [8]]
        $result = WampMessage::fromArray([50, $pending->requestId, [], [8]]);
        $this->caller->onResult($result);

        expect($future->await())->toBe(8);
    });

    it('rigetta il Future quando arriva ERROR', function () {
        $this->session->shouldReceive('send')->once();

        $future = $this->caller->callAsync('com.myapp.test', [3, 5]);

        $pending = collect(
            (new ReflectionProperty($this->registry, 'pending'))
                ->getValue($this->registry),
        )->first();

        // Simuliamo ERROR dal router
        // [8, CALL, requestId, {}, "wamp.error.no_such_procedure"]
        $error = WampMessage::fromArray([8, 48, $pending->requestId, [], 'wamp.error.no_such_procedure']);
        $this->caller->onError($error);

        expect(fn () => $future->await())
            ->toThrow(RpcException::class, 'no_such_procedure');
    });

    it('ignora RESULT per requestId sconosciuto', function () {
        // Non deve lanciare eccezioni
        $unknown = WampMessage::fromArray([50, 99999, [], [42]]);
        $this->caller->onResult($unknown);

        expect(true)->toBeTrue();
    });
});
