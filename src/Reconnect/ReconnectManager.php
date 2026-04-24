<?php

namespace Hermod\Reconnect;

use Hermod\Contracts\ReconnectStrategyContract;
use Hermod\Exceptions\ReconnectException;
use Throwable;

class ReconnectManager
{
    private bool $reconnecting = false;

    /**
     * Summary of __construct
     */
    public function __construct(
        private readonly ReconnectStrategyContract $strategy,
        private readonly bool $enabled,
    ) {}

    /**
     * Summary of isEnabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Summary of isReconnecting
     */
    public function isReconnecting(): bool
    {
        return $this->reconnecting;
    }

    /**
     * Esegue il reconnect con backoff esponenziale.
     * Chiama $connectFn finché non riesce o i tentativi si esauriscono.
     *
     * @param  callable  $connectFn  Funzione che tenta la connessione
     * @param  callable  $onSuccess  Chiamata dopo reconnect riuscito
     *
     * @throws ReconnectException
     */
    public function reconnect(callable $connectFn, callable $onSuccess): void
    {
        if (! $this->enabled) {
            throw new ReconnectException(
                'Reconnect automatico disabilitato nella configurazione.',
            );
        }

        $this->reconnecting = true;
        $this->strategy->reset();

        while ($this->strategy->shouldRetry()) {
            $delay = $this->strategy->nextDelay();
            $attempt = $this->strategy->attempts() + 1;

            // Aspettiamo prima di ritentare
            \Amp\delay($delay);

            try {
                $connectFn();
                $this->strategy->reset();
                $this->reconnecting = false;
                $onSuccess();

                return;
            } catch (Throwable $e) {
                $this->strategy->recordFailure();

                // Se è l'ultimo tentativo lanciamo eccezione
                if (! $this->strategy->shouldRetry()) {
                    $this->reconnecting = false;
                    throw new ReconnectException(
                        "Reconnect fallito dopo {$attempt} tentativi: {$e->getMessage()}",
                        previous: $e,
                    );
                }
            }
        }

        $this->reconnecting = false;
    }
}
