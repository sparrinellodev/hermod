<?php

namespace Hermod\Client;

use Amp\Future;
use Hermod\Contracts\WampClientContract;
use Hermod\Exceptions\TransportException;
use Hermod\Exceptions\WampClientException;
use Hermod\PubSub\Publisher;
use Hermod\PubSub\Subscriber;
use Hermod\PubSub\Subscription;
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
        private readonly Publisher $publisher,
        private readonly Subscriber $subscriber,
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
        if (! $this->isConnected()) {
            return;
        }

        $this->listening = false;

        try {
            $this->session->goodbye();
        } catch (\Throwable) {
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

        $future = $this->caller->callAsync($procedure, $args, $kwargs);

        while (! $future->isComplete()) {
            try {
                $message = $this->session->receive();
                $this->dispatcher->dispatch($message);
            } catch (TransportException $e) {
                throw new WampClientException(
                    "Connessione persa durante l'attesa del risultato: {$e->getMessage()}",
                    previous: $e,
                );
            }
        }

        return $future->await();
    }

    /** @param array<mixed> $args
     * @param  array<mixed>  $kwargs
     * @return Future<mixed>
     */
    public function callAsync(string $procedure, array $args = [], array $kwargs = []): Future
    {
        $this->ensureConnected();

        return \Amp\async(function () use ($procedure, $args, $kwargs): mixed {
            $future = $this->caller->callAsync($procedure, $args, $kwargs);

            while (! $future->isComplete()) {
                try {
                    $message = $this->session->receive();
                    $this->dispatcher->dispatch($message);
                } catch (TransportException $e) {
                    throw new WampClientException(
                        "Connessione persa durante l'attesa del risultato: {$e->getMessage()}",
                        previous: $e,
                    );
                }
            }

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
    // PublisherContract
    // -------------------------------------------------------------------------
    public function publish(string $topic, array $args = [], array $kwargs = []): void
    {
        $this->ensureConnected();
        $this->publisher->publish($topic, $args, $kwargs);
    }

    public function publishWithAck(string $topic, array $args = [], array $kwargs = []): Future
    {
        $this->ensureConnected();

        return \Amp\async(function () use ($topic, $args, $kwargs): int {
            $future = $this->publisher->publishWithAck($topic, $args, $kwargs);

            while (! $future->isComplete()) {
                try {
                    $message = $this->session->receive();
                    $this->dispatcher->dispatch($message);
                } catch (TransportException $e) {
                    throw new WampClientException(
                        "Connessione persa durante l'attesa di PUBLISHED: {$e->getMessage()}",
                        previous: $e,
                    );
                }
            }

            return $future->await();
        });
    }

    // -------------------------------------------------------------------------
    // SubscriberContract
    // -------------------------------------------------------------------------

    public function subscribe(string $topic, callable $handler): Subscription
    {
        $this->ensureConnected();

        return $this->subscriber->subscribe($topic, $handler);
    }

    public function unsubscribe(string $topic): void
    {
        $this->ensureConnected();
        $this->subscriber->unsubscribe($topic);
    }

    public function unsubscribeById(Subscription $subscription): void
    {
        $this->ensureConnected();
        $this->subscriber->unsubscribeById($subscription);
    }

    public function getSubscriptions(): array
    {
        return $this->subscriber->getSubscriptions();
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
            } catch (TransportException $e) {
                // La connessione è caduta — usciamo dal loop
                // senza tentare di inviare GOODBYE (il transport è già chiuso)
                $this->listening = false;
                throw new WampClientException(
                    "Connessione persa: {$e->getMessage()}",
                    previous: $e,
                );
            } catch (WampClientException $e) {
                $this->listening = false;
                throw $e;
            } catch (\Throwable $e) {
                $this->listening = false;
                throw new WampClientException(
                    "Errore nel loop di ricezione: {$e->getMessage()}",
                    previous: $e,
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
