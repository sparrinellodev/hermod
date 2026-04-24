<?php

namespace Hermod\Session;

use Hermod\Contracts\AuthenticatorContract;
use Hermod\Contracts\SerializerContract;
use Hermod\Contracts\SessionContract;
use Hermod\Contracts\TransportContract;
use Hermod\Exceptions\AuthenticationException;
use Hermod\Exceptions\SessionException;
use Hermod\Exceptions\WampProtocolException;
use Hermod\Message\MessageFactory;
use Hermod\Message\MessageType;
use Hermod\Message\WampMessage;
use Throwable;

class WampSession implements SessionContract
{
    private SessionState $state = SessionState::Closed;

    private ?int $sessionId = null;

    /** @var array<mixed> */
    private array $routerDetails = [];

    /**
     * Summary of __construct
     */
    public function __construct(
        private readonly TransportContract $transport,
        private readonly SerializerContract $serializer,
        private readonly string $realm,
        private readonly AuthenticatorContract $authenticator,
    ) {}

    // -------------------------------------------------------------------------
    // Ciclo di vita
    // -------------------------------------------------------------------------

    /**
     * Summary of hello
     *
     * @throws SessionException
     */
    public function hello(): void
    {
        $this->assertState(SessionState::Closed, 'hello');

        // Apre la connessione WebSocket
        $this->transport->connect();
        $this->state = SessionState::Establishing;

        // Invia HELLO
        $this->sendMessage(
            MessageFactory::helloWithAuth(
                realm: $this->realm,
                authMethod: $this->authenticator->method()->value,
                authId: $this->authenticator->authId(),
                authExtra: $this->authenticator->authExtra(),
            ),
        );

        $this->handleAuthSequence();
        // Attende WELCOME o ABORT
        /*$message = $this->receiveMessage();

        match ($message->type()) {
            MessageType::WELCOME => $this->handleWelcome($message),
            MessageType::ABORT => $this->handleAbort($message),
            default => throw new SessionException(
                "Risposta inattesa durante l'handshake WAMP: {$message->type()->name}",
            ),
        };*/
    }

    public function goodbye(): void
    {
        // Se la sessione non è stabilita non c'è nulla da chiudere
        if ($this->state !== SessionState::Established) {
            $this->closeSession();

            return;
        }

        $this->state = SessionState::Closing;

        try {
            $this->sendMessage(MessageFactory::goodbye());
            $this->receiveMessage();
        } catch (Throwable) {
        } finally {
            $this->closeSession();
        }
    }

    // -------------------------------------------------------------------------
    // Invio e ricezione messaggi (usati anche da Caller/Callee)
    // -------------------------------------------------------------------------

    /**
     * Summary of send
     */
    public function send(WampMessage $message): void
    {
        $this->assertState(SessionState::Established, 'send');
        $this->sendMessage($message);
    }

    /**
     * Summary of receive
     */
    public function receive(): WampMessage
    {
        $this->assertState(SessionState::Established, 'receive');

        return $this->receiveMessage();
    }

    // -------------------------------------------------------------------------
    // Implementazione SessionContract
    // -------------------------------------------------------------------------

    /**
     * Summary of getSessionId
     */
    public function getSessionId(): ?int
    {
        return $this->sessionId;
    }

    /**
     * Summary of getRealm
     */
    public function getRealm(): string
    {
        return $this->realm;
    }

    /**
     * Summary of isEstablished
     */
    public function isEstablished(): bool
    {
        return $this->state === SessionState::Established;
    }

    /**
     * Summary of getState
     */
    public function getState(): SessionState
    {
        return $this->state;
    }

    /**
     * Summary of getRouterDetails
     *
     * @return array<mixed>
     */
    public function getRouterDetails(): array
    {
        return $this->routerDetails;
    }

    /**
     * Summary of getAuthenticator
     */
    public function getAuthenticator(): AuthenticatorContract
    {
        return $this->authenticator;
    }

    /**
     * Summary of handleAuthSequence
     *
     * @throws SessionException
     */
    private function handleAuthSequence(): void
    {
        $message = $this->receiveMessage();

        match ($message->type()) {
            MessageType::WELCOME => $this->handleWelcome($message),
            MessageType::CHALLENGE => $this->handleChallenge($message),
            MessageType::ABORT => $this->handleAbort($message),
            default => throw new SessionException(
                "Risposta inattesa durante l'handshake WAMP: {$message->type()->name}",
            ),
        };
    }

    /**
     * Summary of handleChallenge
     *
     * @throws AuthenticationException
     * @throws SessionException
     */
    private function handleChallenge(WampMessage $message): void
    {
        // [4, authmethod, extra]
        $authMethod = (string) ($message->get(1) ?? '');
        $extra = (array) ($message->get(2) ?? []);

        if (! $this->authenticator->requiresChallenge()) {
            throw new AuthenticationException(
                "Il router ha inviato una CHALLENGE ma l'authenticator ".
                    "'{$this->authenticator->method()->value}' non la supporta.",
            );
        }

        // Calcoliamo la risposta alla challenge
        $signature = $this->authenticator->handleChallenge(
            challenge: $extra['challenge'] ?? '',
            extra: $extra,
        );

        if ($signature === null) {
            throw new AuthenticationException(
                'Impossibile generare la firma per la challenge WAMP.',
            );
        }

        // Inviamo AUTHENTICATE
        $this->sendMessage(MessageFactory::authenticate($signature));

        // Attendiamo WELCOME o ABORT
        $response = $this->receiveMessage();

        match ($response->type()) {
            MessageType::WELCOME => $this->handleWelcome($response),
            MessageType::ABORT => $this->handleAbort($response),
            default => throw new SessionException(
                "Risposta inattesa dopo AUTHENTICATE: {$response->type()->name}",
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Handlers messaggi in ingresso
    // -------------------------------------------------------------------------

    /**
     * Summary of handleWelcome
     *
     * @throws SessionException
     */
    private function handleWelcome(WampMessage $message): void
    {
        $sessionId = $message->get(1);

        if (! is_int($sessionId)) {
            throw new SessionException(
                'Session ID non valido ricevuto nel messaggio WELCOME.',
            );
        }

        $this->sessionId = $sessionId;
        $this->routerDetails = $message->get(2) ?? [];
        $this->state = SessionState::Established;
    }

    /**
     * Summary of handleAbort
     *
     * @return never
     *
     * @throws WampProtocolException
     */
    private function handleAbort(WampMessage $message): void
    {
        $reason = $message->get(2) ?? 'wamp.error.unknown';
        $details = $message->get(1) ?? [];

        $this->closeSession();

        $isAuthError = str_contains($reason, 'not_authorized')
            || str_contains($reason, 'authentication');

        if ($isAuthError) {
            throw new AuthenticationException(
                "Autenticazione rifiutata dal router: {$reason}",
                wampError: $reason,
                details: $details,
            );
        }

        throw new WampProtocolException(
            "Connessione WAMP rifiutata dal router: {$reason}",
            wampError: $reason,
            details: $details,
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Summary of sendMessage
     */
    private function sendMessage(WampMessage $message): void
    {
        $raw = $this->serializer->serialize($message->toArray());
        $this->transport->send($raw);
    }

    /**
     * Summary of receiveMessage
     */
    private function receiveMessage(): WampMessage
    {
        $raw = $this->transport->receive();
        $data = $this->serializer->deserialize($raw);

        return WampMessage::fromArray($data);
    }

    /**
     * Summary of closeSession
     */
    private function closeSession(): void
    {
        $this->state = SessionState::Closed;
        $this->sessionId = null;

        try {
            $this->transport->close();
        } catch (Throwable) {
        }
    }

    /**
     * Summary of assertState
     *
     * @throws SessionException
     */
    private function assertState(SessionState $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new SessionException(
                "Impossibile eseguire '{$operation}': ".
                    "stato attuale '{$this->state->name}', ".
                    "stato richiesto '{$expected->name}'.",
            );
        }
    }
}
