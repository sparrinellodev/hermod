<?php

namespace Hermod\Rpc;

use Hermod\Contracts\CalleeContract;
use Hermod\Exceptions\CalleeException;
use Hermod\Exceptions\RpcException;
use Hermod\Message\MessageFactory;
use Hermod\Message\MessageType;
use Hermod\Message\WampMessage;
use Hermod\Session\WampSession;

class Callee implements CalleeContract
{
    /** @var array<string, int> procedura → requestId pendente della registrazione */
    private array $pendingRegistrations = [];

    /** @var array<int, Registration> registrationId → Registration */
    private array $registrations = [];

    public function __construct(
        private readonly WampSession         $session,
        private readonly RequestIdGenerator  $idGenerator,
    ) {}

    // -------------------------------------------------------------------------
    // CalleeContract
    // -------------------------------------------------------------------------

    public function register(string $procedure, callable $handler): void
    {
        if ($this->isProcedureRegistered($procedure)) {
            throw new CalleeException(
                "La procedura '{$procedure}' è già registrata."
            );
        }

        $requestId = $this->idGenerator->generate();
        $this->pendingRegistrations[$procedure] = $requestId;

        $this->session->send(
            MessageFactory::register($requestId, $procedure)
        );

        // Salviamo l'handler — lo associeremo al registrationId in onRegistered()
        // usiamo il requestId come chiave temporanea
        $this->pendingRegistrations[$procedure] = [
            'requestId' => $requestId,
            'handler'   => $handler,
        ];
    }

    public function unregister(string $procedure): void
    {
        $registration = $this->findRegistrationByProcedure($procedure);

        if ($registration === null) {
            throw new CalleeException(
                "La procedura '{$procedure}' non è registrata."
            );
        }

        $requestId = $this->idGenerator->generate();

        $this->session->send(
            MessageFactory::unregister($requestId, $registration->registrationId)
        );

        unset($this->registrations[$registration->registrationId]);
    }

    public function getRegistrations(): array
    {
        $result = [];

        foreach ($this->registrations as $registration) {
            $result[$registration->procedure] = $registration->handler;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Gestione messaggi in ingresso
    // -------------------------------------------------------------------------

    /**
     * Chiamato dal MessageDispatcher quando arriva REGISTERED.
     * [65, requestId, registrationId]
     */
    public function onRegistered(WampMessage $message): void
    {
        $requestId      = (int) $message->get(1);
        $registrationId = (int) $message->get(2);

        // Troviamo il pending tramite requestId
        $pending = $this->findPendingByRequestId($requestId);

        if ($pending === null) {
            return; // requestId sconosciuto, ignoriamo
        }

        ['procedure' => $procedure, 'handler' => $handler] = $pending;

        // Salviamo la registrazione definitiva
        $this->registrations[$registrationId] = new Registration(
            registrationId: $registrationId,
            procedure: $procedure,
            handler: $handler,
        );

        // Rimuoviamo dal pending
        unset($this->pendingRegistrations[$procedure]);
    }

    /**
     * Chiamato dal MessageDispatcher quando arriva INVOCATION.
     * [68, requestId, registrationId, details, args?, kwargs?]
     */
    public function onInvocation(WampMessage $message): void
    {
        $requestId      = (int) $message->get(1);
        $registrationId = (int) $message->get(2);
        $args           = $message->get(4) ?? [];
        $kwargs         = $message->get(5) ?? [];

        if (!isset($this->registrations[$registrationId])) {
            // Registrazione sconosciuta — inviamo errore al router
            $this->session->send(
                MessageFactory::yieldError(
                    $requestId,
                    'wamp.error.no_such_registration',
                )
            );
            return;
        }

        $registration = $this->registrations[$registrationId];

        try {
            // Eseguiamo l'handler registrato
            $result = ($registration->handler)(
                is_array($args)   ? $args   : [],
                is_array($kwargs) ? $kwargs : [],
            );

            // Normalizziamo il risultato in array
            $resultArgs = is_array($result) ? $result : [$result];

            // Inviamo YIELD al router
            $this->session->send(
                MessageFactory::yield($requestId, $resultArgs)
            );
        } catch (\Throwable $e) {
            // Se l'handler lancia un'eccezione inviamo ERROR al router
            $this->session->send(
                MessageFactory::yieldError(
                    $requestId,
                    'wamp.error.runtime_error',
                    [$e->getMessage()],
                )
            );
        }
    }

    /**
     * Chiamato dal MessageDispatcher quando arriva UNREGISTERED.
     * [67, requestId]
     */
    public function onUnregistered(WampMessage $message): void
    {
        // In Fase 1 la conferma è implicita — la rimozione avviene già in unregister()
        // Qui possiamo aggiungere logging o eventi in futuro
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function isProcedureRegistered(string $procedure): bool
    {
        return $this->findRegistrationByProcedure($procedure) !== null
            || isset($this->pendingRegistrations[$procedure]);
    }

    private function findRegistrationByProcedure(string $procedure): ?Registration
    {
        foreach ($this->registrations as $registration) {
            if ($registration->procedure === $procedure) {
                return $registration;
            }
        }

        return null;
    }

    private function findPendingByRequestId(int $requestId): ?array
    {
        foreach ($this->pendingRegistrations as $procedure => $pending) {
            if ($pending['requestId'] === $requestId) {
                return [...$pending, 'procedure' => $procedure];
            }
        }

        return null;
    }
}
