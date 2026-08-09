<?php

namespace Hermod\LaravelWamp\Reconnect;

use Hermod\LaravelWamp\Contracts\ReconnectStrategyContract;

/**
 * Implements an exponential backoff retry strategy for handling connection drops.
 *
 * Automatically increases the delay between reconnection attempts exponentially 
 * up to a configured maximum delay, while tracking attempt counts and supporting resets.
 */
class ExponentialBackoffStrategy implements ReconnectStrategyContract
{
    /** @var int The current number of failed attempts recorded. */
    private int $attemptCount = 0;

    /** @var float The delay in seconds before the next reconnection attempt. */
    private float $currentDelay;

    /**
     * Create a new ExponentialBackoffStrategy instance.
     *
     * @param  int  $maxAttempts  The maximum number of allowed retry attempts before giving up.
     * @param  float  $baseDelay  The initial delay in seconds for the first retry.
     * @param  float  $maxDelay  The maximum upper bound delay in seconds between retries.
     * @param  float  $multiplier  The multiplication factor applied to the delay on each subsequent failure.
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
     * Determine whether another reconnection attempt should be made.
     *
     * @return bool True if attempt count is under the maximum threshold, false otherwise.
     */
    public function shouldRetry(): bool
    {
        return $this->attemptCount < $this->maxAttempts;
    }

    /**
     * Get the delay duration in seconds before the next reconnection attempt.
     *
     * @return float The current delay in seconds.
     */
    public function nextDelay(): float
    {
        return $this->currentDelay;
    }

    /**
     * Record a connection failure, incrementing attempt count and calculating the next exponential delay.
     */
    public function recordFailure(): void
    {
        $this->attemptCount++;

        // Exponential backoff calculation capped at the maximum allowable delay
        $this->currentDelay = min(
            $this->currentDelay * $this->multiplier,
            $this->maxDelay,
        );
    }

    /**
     * Reset the retry strategy state back to initial values upon a successful connection.
     */
    public function reset(): void
    {
        $this->attemptCount = 0;
        $this->currentDelay = $this->baseDelay;
    }

    /**
     * Get the total number of recorded failure attempts.
     *
     * @return int The attempt count.
     */
    public function attempts(): int
    {
        return $this->attemptCount;
    }
}