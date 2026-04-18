<?php

namespace Hermod\Rpc;

use Amp\Future;
use Hermod\Contracts\CallerContract;
use Hermod\Exceptions\RpcException;
use Hermod\Message\MessageFactory;
use Hermod\Message\WampMessage;
use Hermod\Session\WampSession;

class Caller implements CallerContract
{
    public function __construct(
        private readonly WampSession $session,
        private readonly PendingCallRegistry $registry,
    ) {}

    // -------------------------------------------------------------------------
    // CallerContract
    // -------------------------------------------------------------------------

    /**
     * Chiamata RPC sincrona — blocca fino alla risposta.
     *
     * @param  array<mixed>  $args
     * @param  array<mixed>  $kwargs
     */
    public function call(string $procedure, array $args = [], array $kwargs = []): mixed
    {
        return $this->callAsync($procedure, $args, $kwargs)->await();
    }

    /**
     * Chiamata RPC asincrona — restituisce un Future.
     *
     * @param  array<mixed>  $args
     * @param  array<mixed>  $kwargs
     * @return Future<mixed>
     */
    public function callAsync(string $procedure, array $args = [], array $kwargs = []): Future
    {
        // 1. Registra la chiamata pendente
        $pending = $this->registry->register($procedure);

        // 2. Invia CALL al router
        $this->session->send(
            MessageFactory::call(
                requestId: $pending->requestId,
                procedure: $procedure,
                args: $args,
                kwargs: $kwargs,
            ),
        );

        // 3. Restituisce il Future — si risolverà quando arriverà RESULT/ERROR
        return $pending->getFuture();
    }

    // -------------------------------------------------------------------------
    // Gestione messaggi in ingresso
    // -------------------------------------------------------------------------

    /**
     * Chiamato dal MessageDispatcher quando arriva RESULT.
     * [50, requestId, details, args?, kwargs?]
     */
    public function onResult(WampMessage $message): void
    {
        $requestId = (int) $message->get(1);
        $args = $message->get(3) ?? [];
        $kwargs = $message->get(4) ?? [];

        try {
            $pending = $this->registry->pull($requestId);
        } catch (RpcException) {
            // requestId sconosciuto — ignoriamo silenziosamente
            return;
        }

        // Se c'è un solo valore restituiamo quello direttamente,
        // altrimenti restituiamo l'array completo
        $result = match (true) {
            ! empty($kwargs) => $kwargs,
            count($args) === 1 => $args[0],
            count($args) > 1 => $args,
            default => null,
        };

        $pending->resolve($result);
    }

    /**
     * Chiamato dal MessageDispatcher quando arriva ERROR su una CALL.
     * [8, CALL, requestId, details, error, args?, kwargs?]
     */
    public function onError(WampMessage $message): void
    {
        $requestId = (int) $message->get(2);
        $wampError = (string) ($message->get(4) ?? 'wamp.error.unknown');
        $args = $message->get(5) ?? [];

        try {
            $pending = $this->registry->pull($requestId);
        } catch (RpcException) {
            return;
        }

        $pending->reject(new RpcException(
            message: "Chiamata RPC '{$pending->procedure}' fallita: {$wampError}",
            wampError: $wampError,
            args: is_array($args) ? $args : [],
        ));
    }
}
