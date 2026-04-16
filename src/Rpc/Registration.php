<?php

namespace Hermod\Rpc;

class Registration
{
    public function __construct(
        public readonly int      $registrationId,
        public readonly string   $procedure,
        public readonly callable $handler,
    ) {}
}
