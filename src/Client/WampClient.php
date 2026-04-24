<?php

namespace Hermod\Client;

use Amp\Future;
use Hermod\Contracts\WampClientContract;
use Hermod\Exceptions\TransportException;
use Hermod\Exceptions\WampClientException;
use Hermod\Exceptions\WampProtocolException;
use Hermod\PubSub\Publisher;
use Hermod\PubSub\Subscriber;
use Hermod\PubSub\Subscription;
use Hermod\Reconnect\ReconnectManager;
use Hermod\Rpc\Callee;
use Hermod\Rpc\Caller;
use Hermod\Rpc\MessageDispatcher;
use Hermod\Session\WampSession;
use Throwable;

class WampClient implements WampClientContract
{
    private bool $listening = false;

    public function __construct(
        private readonly WampSession $session,
        private readonly Caller $caller,
        private readonly Callee $callee,
        private readonly Publisher $publisher,
        private readonly Subscriber $subscriber,
        private readonly MessageDispatcher $dispatcher,
        private readonly ReconnectManager $reconnectManager,
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
        } catch (Throwable) {
        }
    }

    public function isConnected(): bool
    {
        return $this->session->isEstablished();
    }

    // -------------------------------------------------------------------------
    // CallerContract
    // -------------------------------------------------------------------------

    /**
     * Summary of call
     * @param string $procedure
     * @param array<mixed> $args
     * @param array<mixed> $kwargs
     * @throws WampClientException
     * @return mixed
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

    /**
     * Summary of callAsync
     * @param string $procedure
     * @param array<mixed> $args
     * @param array<mixed> $kwargs
     * @return Future
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

    /**
     * Summary of register
     * @param string $procedure
     * @param callable $handler
     * @return void
     */
    public function register(string $procedure, callable $handler): void
    {
        $this->ensureConnected();

        $this->callee->register($procedure, $handler);
    }

    /**
     * Summary of unregister
     * @param string $procedure
     * @return void
     */
    public function unregister(string $procedure): void
    {
        $this->ensureConnected();

        $this->callee->unregister($procedure);
    }

    /**
     * Summary of getRegistrations
     * @return callable[]
     */
    public function getRegistrations(): array
    {
        return $this->callee->getRegistrations();
    }

    /**
     * Summary of publish
     * @param string $topic
     * @param array<mixed> $args
     * @param array<mixed> $kwargs
     * @return void
     */
    public function publish(string $topic, array $args = [], array $kwargs = []): void
    {
        $this->ensureConnected();
        $this->publisher->publish($topic, $args, $kwargs);
    }

    /**
     * Summary of publishWithAck
     * @param string $topic
     * @param array<mixed> $args
     * @param array<mixed> $kwargs
     * @return Future<int>
     */
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

    /**
     * Summary of subscribe
     * @param string $topic
     * @param callable $handler
     * @return Subscription
     */
    public function subscribe(string $topic, callable $handler): Subscription
    {
        $this->ensureConnected();

        return $this->subscriber->subscribe($topic, $handler);
    }

    /**
     * Summary of unsubscribe
     * @param string $topic
     * @return void
     */
    public function unsubscribe(string $topic): void
    {
        $this->ensureConnected();
        $this->subscriber->unsubscribe($topic);
    }

    /**
     * Summary of unsubscribeById
     * @param Subscription $subscription
     * @return void
     */
    public function unsubscribeById(Subscription $subscription): void
    {
        $this->ensureConnected();
        $this->subscriber->unsubscribeById($subscription);
    }

    /**
     * Summary of getSubscriptions
     * @return Subscription[]
     */
    public function getSubscriptions(): array
    {
        return $this->subscriber->getSubscriptions();
    }

    // -------------------------------------------------------------------------
    // Message Loop
    // -------------------------------------------------------------------------

    /**
     * Summary of listen
     * Avvia il loop di ricezione messaggi.
     * Rimane in ascolto finché la connessione è attiva o viene chiamato disconnect().
     * @throws WampClientException
     * @return void
     */
    public function listen(): void
    {
        $this->ensureConnected();
        $this->listening = true;

        while ($this->listening && $this->isConnected()) {
            try {
                $message = $this->session->receive();
                $this->dispatcher->dispatch($message);
            } catch (WampProtocolException) {
                // GOODBYE dal router — uscita pulita
                $this->listening = false;

                return;
            } catch (TransportException $e) {
                // Connessione persa — tentiamo reconnect
                if ($this->reconnectManager->isEnabled()) {
                    $this->handleReconnect();
                } else {
                    $this->listening = false;
                    throw new WampClientException(
                        "Connessione persa: {$e->getMessage()}",
                        previous: $e,
                    );
                }
            } catch (WampClientException $e) {
                $this->listening = false;
                throw $e;
            } catch (Throwable $e) {
                $this->listening = false;
                throw new WampClientException(
                    "Errore nel loop di ricezione: {$e->getMessage()}",
                    previous: $e,
                );
            }
        }
    }

    /**
     * Summary of tick
     * Elabora un singolo messaggio in arrivo senza bloccare.
     * @return void
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

    /**
     * Summary of getSessionId
     * @return int|null
     */
    public function getSessionId(): ?int
    {
        return $this->session->getSessionId();
    }

    /**
     * Summary of getRealm
     * @return string
     */
    public function getRealm(): string
    {
        return $this->session->getRealm();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Summary of ensureConnected
     * @throws WampClientException
     * @return void
     */
    private function ensureConnected(): void
    {
        if (! $this->isConnected()) {
            throw new WampClientException(
                'Client non connesso. Chiamare connect() prima di eseguire operazioni.',
            );
        }
    }

    /**
     * Summary of handleReconnect
     * @return void
     */
    private function handleReconnect(): void
    {
        $registrations = $this->callee->getRegistrations();
        $subscriptions = $this->subscriber->getSubscriptions();

        $this->reconnectManager->reconnect(
            connectFn: fn() => $this->session->hello(),
            onSuccess: function () use ($registrations, $subscriptions) {
                // Ri-registriamo tutte le procedure
                foreach ($registrations as $procedure => $handler) {
                    $this->callee->register($procedure, $handler);
                }

                // Ri-sottoscriviamo tutti i topic
                foreach ($subscriptions as $topic => $subscription) {
                    $this->subscriber->subscribe($topic, $subscription->handler);
                }
            },
        );
    }
}
