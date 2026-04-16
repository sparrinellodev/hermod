<?php

namespace Hermod\Rpc;

use Hermod\Exceptions\WampProtocolException;
use Hermod\Message\MessageType;
use Hermod\Message\WampMessage;
use Hermod\Session\WampSession;

class MessageDispatcher
{
    public function __construct(
        private readonly WampSession $session,
        private readonly Caller      $caller,
        private readonly Callee      $callee,
    ) {}

    /**
     * Smista il messaggio ricevuto al gestore corretto.
     */
    public function dispatch(WampMessage $message): void
    {
        match ($message->type()) {
            // RPC - Caller
            MessageType::RESULT      => $this->caller->onResult($message),
            MessageType::ERROR       => $this->dispatchError($message),

            // RPC - Callee
            MessageType::REGISTERED   => $this->callee->onRegistered($message),
            MessageType::UNREGISTERED => $this->callee->onUnregistered($message),
            MessageType::INVOCATION   => $this->callee->onInvocation($message),

            // Gestione GOODBYE inatteso (router che chiude la connessione)
            MessageType::GOODBYE      => $this->handleUnexpectedGoodbye($message),

            // Messaggi non gestiti in Fase 1 — ignorati silenziosamente
            default => null,
        };
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function dispatchError(WampMessage $message): void
    {
        // [8, requestType, requestId, ...]
        $requestType = MessageType::tryFrom((int) $message->get(1));

        match ($requestType) {
            MessageType::CALL     => $this->caller->onError($message),
            MessageType::REGISTER => null, // Fase 1 — gestiamo in futuro
            default               => null,
        };
    }

    private function handleUnexpectedGoodbye(WampMessage $message): void
    {
        $reason = (string) ($message->get(2) ?? 'wamp.close.unknown');

        throw new WampProtocolException(
            "Il router ha chiuso la sessione inaspettatamente: {$reason}",
            wampError: $reason,
        );
    }
}
