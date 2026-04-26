<?php

namespace Hermod\Serializer;

use Hermod\Contracts\SerializerContract;
use Hermod\Exceptions\SerializationException;
use MessagePack\MessagePack;
use MessagePack\PackOptions;
use MessagePack\Type\Map;
use MessagePack\UnpackOptions;

class MsgpackSerializer implements SerializerContract
{
    public function serialize(array $message): string
    {
        try {
            return MessagePack::pack(
                $this->normalize($message),
                PackOptions::FORCE_STR,
            );
        } catch (\Throwable $e) {
            throw new SerializationException(
                "Impossibile serializzare il messaggio WAMP in MessagePack: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    public function deserialize(string $raw): array
    {
        try {
            $decoded = MessagePack::unpack($raw, UnpackOptions::BIGINT_AS_STR);
        } catch (\Throwable $e) {
            throw new SerializationException(
                "Impossibile deserializzare il messaggio WAMP da MessagePack: {$e->getMessage()}",
                previous: $e,
            );
        }

        if (! is_array($decoded)) {
            throw new SerializationException(
                'Il messaggio WAMP deserializzato non è un array valido.',
            );
        }

        return $decoded;
    }

    public function subprotocol(): string
    {
        return 'wamp.2.msgpack';
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Normalizza ricorsivamente il messaggio WAMP per la serializzazione MessagePack.
     *
     * - stdClass / array associativo → Map (dizionario MessagePack)
     * - array lista → array MessagePack
     * - scalari → invariati
     */
    private function normalize(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            // stdClass → Map MessagePack (dizionario)
            $map = [];
            foreach ((array) $value as $k => $v) {
                $map[$k] = $this->normalize($v);
            }

            return new Map($map);
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                // Lista posizionale → array MessagePack
                return array_map([$this, 'normalize'], $value);
            }

            // Array associativo → Map MessagePack
            $map = [];
            foreach ($value as $k => $v) {
                $map[$k] = $this->normalize($v);
            }

            return new Map($map);
        }

        return $value;
    }
}
