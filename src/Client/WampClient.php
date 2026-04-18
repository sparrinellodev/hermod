<?php

namespace Hermod\Client;

use Amp\Future;
use Hermod\Contracts\WampClientContract;
use Hermod\Exceptions\WampClientException;
use Hermod\Rpc\Callee;
use Hermod\Rpc\Caller;
use Hermod\Rpc\MessageDispatcher;
use Hermod\Session\WampSession;

class WampClient implements WampClientContract
{
    private bool $listening = false;

    public function __construct(
        private readonly WampSession $session,
        private readonly Caller $caller,
        private readonly Callee $callee,
        private readonly MessageDispatcher $dispatcher,
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

        try {
            $this->session->goodbye();
        } catch (\Throwable) {
            // Se goodbye() fallisce chiudiamo comunque
        }
    }

    public function isConnected(): bool
    {
        return $this->session->isEstablished();
    }

    // -------------------------------------------------------------------------
    // CallerContract
    // -------------------------------------------------------------------------

    /** @param array<mixed> $args
     * @param  array<mixed>  $kwargs
     */
    public function call(string $procedure, array $args = [], array $kwargs = []): mixed
    {
        $this->ensureConnected();

        // 1. Invia CALL e ottieni il Future da risolvere
        $future = $this->caller->callAsync($procedure, $args, $kwargs);

        // 2. Ricevi e smista messaggi finché il nostro RESULT non arriva
        //    receive() cede il controllo all'event loop AMPHP ad ogni chiamata
        while (!$future->isComplete()) {
            try {
                $message = $this->session->receive();
                $this->dispatcher->dispatch($message);
            } catch (\Hermod\Exceptions\TransportException $e) {
                throw new WampClientException(
                    "Connessione persa durante l'attesa del risultato: {$e->getMessage()}",
                    previous: $e
                );
            }
        }

        // 3. Il Future è già completo — await() ritorna immediatamente
        return $future->await();
    }

    /** @param array<mixed> $args
     * @param  array<mixed>  $kwargs
     * @return Future<mixed>
     */
    public function callAsync(string $procedure, array $args = [], array $kwargs = []): Future
    {
        $this->ensureConnected();

        // Avvolgiamo tutto in una fiber AMPHP
        // che gestisce autonomamente il loop di ricezione
        return \Amp\async(function () use ($procedure, $args, $kwargs): mixed {
            // 1. Invia CALL e ottieni il Future interno
            $future = $this->caller->callAsync($procedure, $args, $kwargs);

            // 2. Leggi messaggi finché il RESULT non arriva
            while (!$future->isComplete()) {
                try {
                    $message = $this->session->receive();
                    $this->dispatcher->dispatch($message);
                } catch (\Hermod\Exceptions\TransportException $e) {
                    throw new WampClientException(
                        "Connessione persa durante l'attesa del risultato: {$e->getMessage()}",
                        previous: $e
                    );
                }
            }

            // 3. Future già completo — ritorna immediatamente
            return $future->await();
        });
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
            } catch (\Hermod\Exceptions\TransportException $e) {
                // La connessione è caduta — usciamo dal loop
                // senza tentare di inviare GOODBYE (il transport è già chiuso)
                $this->listening = false;
                throw new WampClientException(
                    "Connessione persa: {$e->getMessage()}",
                    previous: $e
                );
            } catch (WampClientException $e) {
                $this->listening = false;
                throw $e;
            } catch (\Throwable $e) {
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
        if (! $this->isConnected()) {
            throw new WampClientException(
                'Client non connesso. Chiamare connect() prima di eseguire operazioni.',
            );
        }
    }
}
