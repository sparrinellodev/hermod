<?php

namespace Hermod\Serializer;

use Hermod\Contracts\SerializerContract;
use Hermod\Exceptions\SerializationException;

class MsgpackSerializer implements SerializerContract
{
    public function __construct()
    {
        if (! extension_loaded('msgpack')) {
            throw new SerializationException(
                'Estensione PHP "msgpack" non installata. '.
                    'Installala con: pecl install msgpack',
            );
        }
    }

    /**
     * Summary of serialize
     *
     * @param  array<mixed>  $message
     *
     * @throws SerializationException
     */
    public function serialize(array $message): string
    {
        $encoded = msgpack_pack($message);

        if ($encoded === false) {
            throw new SerializationException('Impossibile serializzare il messaggio WAMP in MessagePack.');
        }

        return $encoded;
    }

    /**
     * Summary of deserialize
     *
     * @return array<mixed>
     *
     * @throws SerializationException
     */
    public function deserialize(string $raw): array
    {
        try {
            set_error_handler(function ($severity, $message) {
                throw new SerializationException($message);
            });

            $decoded = msgpack_unpack($raw);

            restore_error_handler();
        } catch (\Throwable $e) {
            restore_error_handler();

            throw new SerializationException(
                "Impossibile deserializzare il messaggio WAMP dal MessagePack: {$e->getMessage()}",
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
}
