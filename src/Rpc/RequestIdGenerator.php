<?php

namespace Hermod\Rpc;

class RequestIdGenerator
{
    // WAMP spec: ID casuali nel range [1, 2^53]
    private const MIN = 1;

    private const MAX = 9007199254740992; // 2^53

    public function generate(): int
    {
        return random_int(self::MIN, self::MAX);
    }
}
