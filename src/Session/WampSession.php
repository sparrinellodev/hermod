<?php

namespace Hermod\Session;

use Hermod\Contracts\SerializerContract;
use Hermod\Contracts\SessionContract;
use Hermod\Contracts\TransportContract;
use Hermod\Exceptions\SessionException;
use Hermod\Exceptions\WampProtocolException;
use Hermod\Message\MessageFactory;
use Hermod\Message\MessageType;
use Hermod\Message\WampMessage;

class WampSession implements SessionContract
{
    private SessionState $state = SessionState::Closed;

    private ?int $sessionId = null;

    /** @var array<mixed> */
    private array $routerDetails = [];

    public function __construct(
        private readonly TransportContract $transport,
        private readonly SerializerContract $serializer,
        private readonly string $realm,
    ) {}

    // -------------------------------------------------------------------------
    // Ciclo di vita
    // -------------------------------------------------------------------------

    public function hello(): void
    {
        $this->assertState(SessionState::Closed, 'hello');

        // Apre la connessione WebSocket
        $this->transport->connect();
        $this->state = SessionState::Establishing;

        // Invia HELLO
        $this->sendMessage(MessageFactory::hello($this->realm));

        // Attende WELCOME o ABORT
        $message = $this->receiveMessage();

        match ($message->type()) {
            MessageType::WELCOME => $this->handleWelcome($message),
            MessageType::ABORT => $this->handleAbort($message),
            default => throw new SessionException(
                "Risposta inattesa durante l'handshake WAMP: {$message->type()->name}",
            ),
        };
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

            // Attendiamo il GOODBYE di conferma dal router
            // ma ignoriamo qualsiasi errore — stiamo già chiudendo
            $this->receiveMessage();
        } catch (\Throwable) {
            // Il router potrebbe aver già chiuso la connessione
            // è normale durante uno shutdown, non propaghiamo
        } finally {
            $this->closeSession();
        }
    }

    // -------------------------------------------------------------------------
    // Invio e ricezione messaggi (usati anche da Caller/Callee)
    // -------------------------------------------------------------------------

    public function send(WampMessage $message): void
    {
        $this->assertState(SessionState::Established, 'send');
        $this->sendMessage($message);
    }

    public function receive(): WampMessage
    {
        $this->assertState(SessionState::Established, 'receive');

        return $this->receiveMessage();
    }

    // -------------------------------------------------------------------------
    // Implementazione SessionContract
    // -------------------------------------------------------------------------

    public function getSessionId(): ?int
    {
        return $this->sessionId;
    }

    public function getRealm(): string
    {
        return $this->realm;
    }

    public function isEstablished(): bool
    {
        return $this->state === SessionState::Established;
    }

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

    // -------------------------------------------------------------------------
    // Handlers messaggi in ingresso
    // -------------------------------------------------------------------------

    private function handleWelcome(WampMessage $message): void
    {
        // [2, sessionId, details]
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

    private function handleAbort(WampMessage $message): void
    {
        // [3, details, reason]
        $reason = $message->get(2) ?? 'wamp.error.unknown';
        $details = $message->get(1) ?? [];

        $this->closeSession();

        throw new WampProtocolException(
            "Connessione WAMP rifiutata dal router: {$reason}",
            wampError: $reason,
            details: is_array($details) ? $details : [],
        );
    }

    // -------------------------------------------------------------------------
    // Helpers interni
    // -------------------------------------------------------------------------

    private function sendMessage(WampMessage $message): void
    {
        $raw = $this->serializer->serialize($message->toArray());
        $this->transport->send($raw);
    }

    private function receiveMessage(): WampMessage
    {
        $raw = $this->transport->receive();
        $data = $this->serializer->deserialize($raw);

        return WampMessage::fromArray($data);
    }

    private function closeSession(): void
    {
        $this->state     = SessionState::Closed;
        $this->sessionId = null;

        try {
            $this->transport->close();
        } catch (\Throwable) {
            // Ignoriamo errori in chiusura del transport
        }
    }

    private function assertState(SessionState $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new SessionException(
                "Impossibile eseguire '{$operation}': " .
                    "stato attuale '{$this->state->name}', " .
                    "stato richiesto '{$expected->name}'.",
            );
        }
    }
}
