<?php

namespace Hermod\Serializer;

use Hermod\Contracts\SerializerContract;
use Hermod\Exceptions\SerializationException;

class MsgpackSerializer implements SerializerContract
{
    public function __construct()
    {
        if (!extension_loaded('msgpack')) {
            throw new SerializationException(
                'Estensione PHP "msgpack" non installata. ' .
                    'Installala con: pecl install msgpack'
            );
        }
    }

    public function serialize(array $message): string
    {
        $encoded = msgpack_pack($message);

        if ($encoded === false) {
            throw new SerializationException('Impossibile serializzare il messaggio WAMP in MessagePack.');
        }

        return $encoded;
    }

    public function deserialize(string $raw): array
    {
        $decoded = msgpack_unpack($raw);

        if (!is_array($decoded)) {
            throw new SerializationException('Il messaggio WAMP deserializzato non è un array valido.');
        }

        return $decoded;
    }

    public function subprotocol(): string
    {
        return 'wamp.2.msgpack';
    }
}
