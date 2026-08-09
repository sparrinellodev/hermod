<?php

use Hermod\LaravelWamp\Auth\AnonymousAuthenticator;
use Hermod\LaravelWamp\Contracts\TransportContract;
use Hermod\LaravelWamp\Exceptions\SessionException;
use Hermod\LaravelWamp\Exceptions\WampProtocolException;
use Hermod\LaravelWamp\Message\MessageType;
use Hermod\LaravelWamp\Message\WampMessage;
use Hermod\LaravelWamp\Serializer\JsonSerializer;
use Hermod\LaravelWamp\Session\SessionState;
use Hermod\LaravelWamp\Session\WampSession;

describe('WampSession', function () {

    beforeEach(function () {
        $this->transport = Mockery::mock(TransportContract::class);
        $this->serializer = new JsonSerializer;
        $this->authenticator = new AnonymousAuthenticator;
        $this->session = new WampSession(
            $this->transport,
            $this->serializer,
            'realm1',
            $this->authenticator,   // ← aggiunto
        );
    });

    afterEach(fn () => Mockery::close());

    it('parte nello stato Closed', function () {
        expect($this->session->getState())->toBe(SessionState::Closed)
            ->and($this->session->isEstablished())->toBeFalse()
            ->and($this->session->getSessionId())->toBeNull();
    });

    it('stabilisce la sessione correttamente con WELCOME', function () {
        $welcome = json_encode([2, 987654, ['roles' => []]]);

        $this->transport->shouldReceive('connect')->once();
        $this->transport->shouldReceive('send')->once();
        $this->transport->shouldReceive('receive')->once()->andReturn($welcome);

        $this->session->hello();

        expect($this->session->isEstablished())->toBeTrue()
            ->and($this->session->getSessionId())->toBe(987654)
            ->and($this->session->getRealm())->toBe('realm1')
            ->and($this->session->getState())->toBe(SessionState::Established);
    });

    it('lancia WampProtocolException quando riceve ABORT', function () {
        $abort = json_encode([3, ['message' => 'Realm inesistente'], 'wamp.error.no_such_realm']);

        $this->transport->shouldReceive('connect')->once();
        $this->transport->shouldReceive('send')->once();
        $this->transport->shouldReceive('receive')->once()->andReturn($abort);
        $this->transport->shouldReceive('close')->once();

        expect(fn () => $this->session->hello())
            ->toThrow(WampProtocolException::class, 'no_such_realm');
    });

    it('lancia SessionException se hello() viene chiamato due volte', function () {
        $welcome = json_encode([2, 111, []]);

        $this->transport->shouldReceive('connect')->once();
        $this->transport->shouldReceive('send')->once();
        $this->transport->shouldReceive('receive')->once()->andReturn($welcome);

        $this->session->hello();

        expect(fn () => $this->session->hello())
            ->toThrow(SessionException::class, 'hello');
    });

    it('chiude correttamente la sessione con goodbye()', function () {
        $welcome = json_encode([2, 111, []]);
        $goodbye = json_encode([6, [], 'wamp.close.normal']);

        $this->transport->shouldReceive('connect')->once();
        $this->transport->shouldReceive('send')->twice();
        $this->transport->shouldReceive('receive')->andReturn($welcome, $goodbye);
        $this->transport->shouldReceive('close')->once();

        $this->session->hello();
        $this->session->goodbye();

        expect($this->session->isEstablished())->toBeFalse()
            ->and($this->session->getState())->toBe(SessionState::Closed)
            ->and($this->session->getSessionId())->toBeNull();
    });

    it('lancia SessionException se send() chiamato senza sessione attiva', function () {
        $message = WampMessage::create(MessageType::CALL, 1, [], 'com.test', []);

        expect(fn () => $this->session->send($message))
            ->toThrow(SessionException::class, 'send');
    });
});
