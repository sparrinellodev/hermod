<?php

namespace Hermod\Reconnect;

use Hermod\Contracts\ReconnectStrategyContract;

class ExponentialBackoffStrategy implements ReconnectStrategyContract
{
    private int $attemptCount = 0;

    private float $currentDelay;

    /**
     * Summary of __construct
     */
    public function __construct(
        private readonly int $maxAttempts,
        private readonly float $baseDelay,
        private readonly float $maxDelay,
        private readonly float $multiplier,
    ) {
        $this->currentDelay = $baseDelay;
    }

    /**
     * Summary of shouldRetry
     */
    public function shouldRetry(): bool
    {
        return $this->attemptCount < $this->maxAttempts;
    }

    /**
     * Summary of nextDelay
     */
    public function nextDelay(): float
    {
        return $this->currentDelay;
    }

    /**
     * Summary of recordFailure
     */
    public function recordFailure(): void
    {
        $this->attemptCount++;

        // Backoff esponenziale con cap al max delay
        $this->currentDelay = min(
            $this->currentDelay * $this->multiplier,
            $this->maxDelay,
        );
    }

    /**
     * Summary of reset
     */
    public function reset(): void
    {
        $this->attemptCount = 0;
        $this->currentDelay = $this->baseDelay;
    }

    /**
     * Summary of attempts
     */
    public function attempts(): int
    {
        return $this->attemptCount;
    }
}
