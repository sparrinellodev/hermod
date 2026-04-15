<?php

namespace Hermod\Serializer;

use Hermod\Contracts\SerializerContract;
use Hermod\Exceptions\SerializationException;

class JsonSerializer implements SerializerContract
{
    public function serialize(array $message): string
    {
        $encoded = json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            throw new SerializationException('Impossibile serializzare il messaggio WAMP in JSON.');
        }

        return $encoded;
    }

    public function deserialize(string $raw): array
    {
        try {
            $decoded = json_decode($raw, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new SerializationException(
                "Impossibile deserializzare il messaggio WAMP dal JSON: {$e->getMessage()}",
                previous: $e
            );
        }

        if (!is_array($decoded)) {
            throw new SerializationException('Il messaggio WAMP deserializzato non è un array valido.');
        }

        return $decoded;
    }

    public function subprotocol(): string
    {
        return 'wamp.2.json';
    }
}
