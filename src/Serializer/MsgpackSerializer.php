<?php

namespace Hermod\Serializer;

use Hermod\Contracts\SerializerContract;
use Hermod\Exceptions\SerializationException;
use MessagePack\MessagePack;
use MessagePack\PackOptions;
use MessagePack\UnpackOptions;
use Throwable;

class MsgpackSerializer implements SerializerContract
{
    /**
     * Summary of serialize
     *
     * @throws SerializationException
     */
    public function serialize(array $message): string
    {
        try {
            return MessagePack::pack($message, PackOptions::FORCE_STR);
        } catch (Throwable $e) {
            throw new SerializationException(
                "Impossibile serializzare il messaggio WAMP in MessagePack: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Summary of deserialize
     *
     * @throws SerializationException
     */
    public function deserialize(string $raw): array
    {
        try {
            $decoded = MessagePack::unpack($raw, UnpackOptions::BIGINT_AS_STR);
        } catch (Throwable $e) {
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

    /**
     * Summary of subprotocol
     */
    public function subprotocol(): string
    {
        return 'wamp.2.msgpack';
    }
}
