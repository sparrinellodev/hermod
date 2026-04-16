<?php

namespace Hermod\Client;

use Hermod\Contracts\WampClientContract;
use Hermod\Exceptions\RpcException;
use Hermod\Exceptions\WampClientException;
use Hermod\Rpc\Callee;
use Hermod\Rpc\Caller;
use Hermod\Rpc\MessageDispatcher;
use Hermod\Session\WampSession;
use Amp\Future;

class WampClient implements WampClientContract
{
    private bool $listening = false;

    public function __construct(
        private readonly WampSession        $session,
        private readonly Caller             $caller,
        private readonly Callee             $callee,
        private readonly MessageDispatcher  $dispatcher,
    ) {}

    // -------------------------------------------------------------------------
    // Connessione
    // -------------------------------------------------------------------------

    public function connect(): void
    {
        if ($this->isConnected()) {
            return;
        }

        $this->session->hello();
    }

    public function disconnect(): void
    {
        if (!$this->isConnected()) {
            return;
        }

        $this->listening = false;
        $this->session->goodbye();
    }

    public function isConnected(): bool
    {
        return $this->session->isEstablished();
    }

    // -------------------------------------------------------------------------
    // CallerContract
    // -------------------------------------------------------------------------

    public function call(string $procedure, array $args = [], array $kwargs = []): mixed
    {
        $this->ensureConnected();

        return $this->caller->call($procedure, $args, $kwargs);
    }

    public function callAsync(string $procedure, array $args = [], array $kwargs = []): Future
    {
        $this->ensureConnected();

        return $this->caller->callAsync($procedure, $args, $kwargs);
    }

    // -------------------------------------------------------------------------
    // CalleeContract
    // -------------------------------------------------------------------------

    public function register(string $procedure, callable $handler): void
    {
        $this->ensureConnected();

        $this->callee->register($procedure, $handler);
    }

    public function unregister(string $procedure): void
    {
        $this->ensureConnected();

        $this->callee->unregister($procedure);
    }

    public function getRegistrations(): array
    {
        return $this->callee->getRegistrations();
    }

    // -------------------------------------------------------------------------
    // Message Loop
    // -------------------------------------------------------------------------

    /**
     * Avvia il loop di ricezione messaggi.
     * Rimane in ascolto finché la connessione è attiva
     * o viene chiamato disconnect().
     *
     * Usato principalmente dal comando Artisan wamp:serve
     * per i Callee che devono restare in ascolto di INVOCATION.
     */
    public function listen(): void
    {
        $this->ensureConnected();

        $this->listening = true;

        while ($this->listening && $this->isConnected()) {
            try {
                $message = $this->session->receive();
                $this->dispatcher->dispatch($message);
            } catch (WampClientException $e) {
                $this->listening = false;
                throw $e;
            } catch (\Throwable $e) {
                // In caso di errore non critico logghiamo e continuiamo
                // In Fase 3 aggiungeremo reconnect automatico
                $this->listening = false;
                throw new WampClientException(
                    "Errore nel loop di ricezione: {$e->getMessage()}",
                    previous: $e
                );
            }
        }
    }

    /**
     * Elabora un singolo messaggio in arrivo senza bloccare.
     * Utile quando si vuole gestire il loop manualmente.
     */
    public function tick(): void
    {
        $this->ensureConnected();

        $message = $this->session->receive();
        $this->dispatcher->dispatch($message);
    }

    // -------------------------------------------------------------------------
    // Informazioni sessione
    // -------------------------------------------------------------------------

    public function getSessionId(): ?int
    {
        return $this->session->getSessionId();
    }

    public function getRealm(): string
    {
        return $this->session->getRealm();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function ensureConnected(): void
    {
        if (!$this->isConnected()) {
            throw new WampClientException(
                'Client non connesso. Chiamare connect() prima di eseguire operazioni.'
            );
        }
    }
}
