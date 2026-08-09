<?php

namespace Hermod\LaravelWamp\Contracts;

/**
 * Defines the contract for defining reconnection backoff and retry strategies.
 *
 * Implementations of this interface manage the logic behind automated 
 * reconnections (e.g., exponential backoff, maximum attempt limits).
 */
interface ReconnectStrategyContract
{
    /**
     * Determine if another reconnection attempt should be made.
     *
     * @return bool True if attempts remain, false if the maximum limit has been reached.
     */
    public function shouldRetry(): bool;

    /**
     * Get the delay duration in seconds before the next reconnection attempt.
     *
     * @return float The delay time in seconds (supporting fractional seconds).
     */
    public function nextDelay(): float;

    /**
     * Record a failed reconnection attempt, updating internal counters and delays.
     */
    public function recordFailure(): void;

    /**
     * Reset the retry counters and state (typically called after a successful reconnection).
     */
    public function reset(): void;

    /**
     * Get the number of failed attempts made so far in the current cycle.
     *
     * @return int The total number of recorded failures.
     */
    public function attempts(): int;
}