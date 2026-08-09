<?php

namespace Hermod\LaravelWamp\Serializer;

use Hermod\LaravelWamp\Contracts\SerializerContract;
use Hermod\LaravelWamp\Exceptions\SerializationException;

/**
 * Factory class responsible for instantiating WAMP protocol serializers dynamically.
 *
 * Resolves requested serializer drivers (e.g., json, msgpack, cbor) from a configured 
 * driver-to-class mapping and ensures the target implementation class exists before creation.
 */
class SerializerFactory
{
    /**
     * Create a new SerializerFactory instance.
     *
     * @param  array<string, class-string<SerializerContract>>  $map  Mapping of driver names to serializer class strings.
     */
    public function __construct(
        private readonly array $map = [],
    ) {
    }

    /**
     * Instantiate and return a serializer implementation based on the given driver name.
     *
     * @param  string  $driver  The driver identifier (e.g., 'json', 'cbor', 'msgpack').
     * @return SerializerContract The instantiated serializer instance.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\SerializationException If the driver is unsupported or its class does not exist.
     */
    public function make(string $driver): SerializerContract
    {
        if (!isset($this->map[$driver])) {
            throw new SerializationException(
                "Serializer driver '{$driver}' is not supported. " .
                'Accepted values: ' . implode(', ', array_keys($this->map)),
            );
        }

        $class = $this->map[$driver];

        if (!class_exists($class)) {
            throw new SerializationException(
                "Serializer implementation class '{$class}' not found.",
            );
        }

        return new $class;
    }
}