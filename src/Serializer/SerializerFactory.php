<?php

namespace Hermod\Serializer;

use Hermod\Contracts\SerializerContract;
use Hermod\Exceptions\SerializationException;

class SerializerFactory
{
    /**
     * @param  array<string, class-string<SerializerContract>>  $map
     */
    public function __construct(
        private readonly array $map = [],
    ) {}

    public function make(string $driver): SerializerContract
    {
        if (! isset($this->map[$driver])) {
            throw new SerializationException(
                "Serializzatore '{$driver}' non supportato. ".
                    'Valori accettati: '.implode(', ', array_keys($this->map)),
            );
        }

        $class = $this->map[$driver];

        if (! class_exists($class)) {
            throw new SerializationException(
                "Classe serializzatore '{$class}' non trovata.",
            );
        }

        return new $class;
    }
}
