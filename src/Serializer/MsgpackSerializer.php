<?php

namespace Hermod\LaravelWamp\Serializer;

use Hermod\LaravelWamp\Contracts\SerializerContract;
use Hermod\LaravelWamp\Exceptions\SerializationException;
use MessagePack\MessagePack;
use MessagePack\PackOptions;
use MessagePack\Type\Map;
use MessagePack\UnpackOptions;

/**
 * MessagePack serializer implementation for WAMP protocol messages.
 *
 * Implements the SerializerContract interface utilizing the `rybakit/msgpack` library 
 * to pack and unpack WAMP message arrays into compact binary MessagePack formats, 
 * with recursive data normalization for associative maps and lists.
 */
class MsgpackSerializer implements SerializerContract
{
    /**
     * Serialize a WAMP message array into a binary MessagePack string.
     *
     * @param  array<mixed>  $message  The message array to pack.
     * @return string The binary MessagePack string representation.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\SerializationException If packing fails.
     */
    public function serialize(array $message): string
    {
        try {
            return MessagePack::pack(
                $this->normalize($message),
                PackOptions::FORCE_STR,
            );
        } catch (\Throwable $e) {
            throw new SerializationException(
                "Failed to serialize WAMP message to MessagePack: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Deserialize a binary MessagePack string into a WAMP message array.
     *
     * @param  string  $raw  The binary MessagePack string received from the transport.
     * @return array<mixed> The decoded message array.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\SerializationException If unpacking fails or the result is not an array.
     */
    public function deserialize(string $raw): array
    {
        try {
            $decoded = MessagePack::unpack($raw, UnpackOptions::BIGINT_AS_STR);
        } catch (\Throwable $e) {
            throw new SerializationException(
                "Failed to deserialize WAMP message from MessagePack: {$e->getMessage()}",
                previous: $e,
            );
        }

        if (!is_array($decoded)) {
            throw new SerializationException(
                'The deserialized WAMP message is not a valid array.',
            );
        }

        return $decoded;
    }

    /**
     * Get the WAMP subprotocol identifier for MessagePack.
     *
     * @return string The subprotocol string ('wamp.2.msgpack').
     */
    public function subprotocol(): string
    {
        return 'wamp.2.msgpack';
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Recursively normalize WAMP data structures for correct MessagePack serialization.
     *
     * - stdClass / associative array → MessagePack Map (dictionary)
     * - list array → MessagePack list array
     * - scalars → unchanged
     *
     * @param  mixed  $value  The value to normalize.
     * @return mixed The normalized value.
     */
    private function normalize(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            // stdClass → MessagePack Map (dictionary)
            $map = [];
            foreach ((array) $value as $k => $v) {
                $map[$k] = $this->normalize($v);
            }

            return new Map($map);
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                // Positional list → MessagePack array
                return array_map([$this, 'normalize'], $value);
            }

            // Associative array → MessagePack Map
            $map = [];
            foreach ($value as $k => $v) {
                $map[$k] = $this->normalize($v);
            }

            return new Map($map);
        }

        return $value;
    }
}