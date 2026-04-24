<?php

use Hermod\Auth\AnonymousAuthenticator;
use Hermod\Auth\TicketAuthenticator;
use Hermod\Auth\WampCraAuthenticator;
use Hermod\Contracts\TransportContract;
use Hermod\Exceptions\AuthenticationException;
use Hermod\Exceptions\WampProtocolException;
use Hermod\Serializer\JsonSerializer;
use Hermod\Session\WampSession;

describe('WampSession — Auth', function () {

    beforeEach(function () {
        $this->transport = Mockery::mock(TransportContract::class);
        $this->serializer = new JsonSerializer;
    });

    afterEach(fn () => Mockery::close());

    // -------------------------------------------------------------------------
    // Anonymous
    // -------------------------------------------------------------------------

    describe('Anonymous auth', function () {

        it('stabilisce la sessione con WELCOME dopo HELLO anonymous', function () {
            $session = new WampSession(
                $this->transport,
                $this->serializer,
                'realm1',
                new AnonymousAuthenticator,
            );

            $welcome = json_encode([2, 111222, ['roles' => []]]);

            $this->transport->shouldReceive('connect')->once();
            $this->transport->shouldReceive('send')->once()->withArgs(function (string $raw) {
                $data = json_decode($raw, true);

                // Verifica che HELLO contenga authmethods anonymous
                return $data[0] === 1
                    && $data[1] === 'realm1'
                    && in_array('anonymous', $data[2]['authmethods'] ?? []);
            });
            $this->transport->shouldReceive('receive')->once()->andReturn($welcome);

            $session->hello();

            expect($session->isEstablished())->toBeTrue()
                ->and($session->getSessionId())->toBe(111222);
        });

        it('non invia authid nel HELLO per anonymous', function () {
            $session = new WampSession(
                $this->transport,
                $this->serializer,
                'realm1',
                new AnonymousAuthenticator,
            );

            $this->transport->shouldReceive('connect')->once();
            $this->transport->shouldReceive('send')->once()->withArgs(function (string $raw) {
                $data = json_decode($raw, true);

                return ! isset($data[2]['authid']);
            });
            $this->transport->shouldReceive('receive')->once()
                ->andReturn(json_encode([2, 999, []]));

            $session->hello();

            expect(true)->toBeTrue();
        });
    });

    // -------------------------------------------------------------------------
    // Ticket auth
    // -------------------------------------------------------------------------

    describe('Ticket auth', function () {

        it('gestisce la sequenza HELLO → CHALLENGE → AUTHENTICATE → WELCOME', function () {
            $session = new WampSession(
                $this->transport,
                $this->serializer,
                'realm1',
                new TicketAuthenticator('user123', 'my-ticket'),
            );

            $challenge = json_encode([4, 'ticket', ['challenge' => '']]);
            $welcome = json_encode([2, 555666, ['roles' => []]]);

            $this->transport->shouldReceive('connect')->once();

            // 1° send → HELLO
            // 2° send → AUTHENTICATE con il ticket
            $this->transport->shouldReceive('send')->twice()->withArgs(function (string $raw) {
                $data = json_decode($raw, true);

                if ($data[0] === 1) {
                    // HELLO — verifica authid e authmethod
                    return $data[2]['authid'] === 'user123'
                        && in_array('ticket', $data[2]['authmethods'] ?? []);
                }

                if ($data[0] === 5) {
                    // AUTHENTICATE — verifica che contenga il ticket
                    return $data[1] === 'my-ticket';
                }

                return false;
            });

            // 1° receive → CHALLENGE, 2° receive → WELCOME
            $this->transport->shouldReceive('receive')
                ->twice()
                ->andReturn($challenge, $welcome);

            $session->hello();

            expect($session->isEstablished())->toBeTrue()
                ->and($session->getSessionId())->toBe(555666);
        });
    });

    // -------------------------------------------------------------------------
    // WAMP-CRA auth
    // -------------------------------------------------------------------------

    describe('WAMP-CRA auth', function () {

        it('gestisce la sequenza HELLO → CHALLENGE → AUTHENTICATE con HMAC', function () {
            $secret = 'my-cra-secret';
            $challenge = 'wampcra-challenge-string';

            $session = new WampSession(
                $this->transport,
                $this->serializer,
                'realm1',
                new WampCraAuthenticator('user123', $secret),
            );

            $expectedSignature = base64_encode(
                hash_hmac('sha256', $challenge, $secret, binary: true),
            );

            $challengeMsg = json_encode([4, 'wampcra', ['challenge' => $challenge]]);
            $welcome = json_encode([2, 777888, ['roles' => []]]);

            $this->transport->shouldReceive('connect')->once();
            $this->transport->shouldReceive('send')->twice()->withArgs(function (string $raw) use ($expectedSignature) {
                $data = json_decode($raw, true);

                if ($data[0] === 5) {
                    // AUTHENTICATE — verifica firma HMAC
                    return $data[1] === $expectedSignature;
                }

                return true; // HELLO ok
            });

            $this->transport->shouldReceive('receive')
                ->twice()
                ->andReturn($challengeMsg, $welcome);

            $session->hello();

            expect($session->isEstablished())->toBeTrue();
        });
    });

    // -------------------------------------------------------------------------
    // Errori di autenticazione
    // -------------------------------------------------------------------------

    describe('Errori auth', function () {

        it('lancia AuthenticationException quando riceve ABORT con errore auth', function () {
            $session = new WampSession(
                $this->transport,
                $this->serializer,
                'realm1',
                new TicketAuthenticator('user', 'wrong-ticket'),
            );

            $challenge = json_encode([4, 'ticket', []]);
            $abort = json_encode([3, ['message' => 'not authorized'], 'wamp.error.not_authorized']);

            $this->transport->shouldReceive('connect')->once();
            $this->transport->shouldReceive('send')->twice();
            $this->transport->shouldReceive('receive')->twice()->andReturn($challenge, $abort);
            $this->transport->shouldReceive('close')->once();

            expect(fn () => $session->hello())
                ->toThrow(AuthenticationException::class, 'not_authorized');
        });

        it('lancia WampProtocolException per ABORT non legato all\'auth', function () {
            $session = new WampSession(
                $this->transport,
                $this->serializer,
                'realm1',
                new AnonymousAuthenticator,
            );

            $abort = json_encode([3, [], 'wamp.error.no_such_realm']);

            $this->transport->shouldReceive('connect')->once();
            $this->transport->shouldReceive('send')->once();
            $this->transport->shouldReceive('receive')->once()->andReturn($abort);
            $this->transport->shouldReceive('close')->once();

            expect(fn () => $session->hello())
                ->toThrow(WampProtocolException::class, 'no_such_realm');
        });

        it('lancia AuthenticationException per CHALLENGE su authenticator che non la supporta', function () {
            // Anonymous non supporta challenge — se il router la manda è un errore
            $session = new WampSession(
                $this->transport,
                $this->serializer,
                'realm1',
                new AnonymousAuthenticator,
            );

            $challenge = json_encode([4, 'wampcra', ['challenge' => 'abc']]);

            $this->transport->shouldReceive('connect')->once();
            $this->transport->shouldReceive('send')->once();
            $this->transport->shouldReceive('receive')->once()->andReturn($challenge);

            expect(fn () => $session->hello())
                ->toThrow(AuthenticationException::class);
        });
    });
});
