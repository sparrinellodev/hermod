<?php

namespace Hermod\Rpc;

use Closure;

class Registration
{
    public function __construct(
        public readonly int $registrationId,
        public readonly string $procedure,
        public readonly Closure $handler,
    ) {}
}
