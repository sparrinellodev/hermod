<?php

namespace Hermod\LaravelWamp\Rpc;

/**
 * Generates unique integer request IDs compliant with the WAMP protocol specification.
 *
 * WAMP message identifiers require safe integers typically ranging from 1 up to $2^{53}$ 
 * (maximum safe integer in JavaScript / JSON numbers). Uses cryptographic randomness 
 * via PHP's random_int to prevent ID collisions.
 */
class RequestIdGenerator
{
    /** @var int Minimum allowed request ID value per WAMP specification. */
    private const MIN = 1;

    /** @var int Maximum allowed request ID value ($2^{53}$). */
    private const MAX = 9007199254740992; // 2^53

    /**
     * Generate a random, cryptographically secure request ID within valid bounds.
     *
     * @return int A unique request ID integer.
     */
    public function generate(): int
    {
        return random_int(self::MIN, self::MAX);
    }
}