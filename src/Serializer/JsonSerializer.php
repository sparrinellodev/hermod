<?php

namespace Hermod\LaravelWamp\Serializer;

use Hermod\LaravelWamp\Contracts\SerializerContract;
use Hermod\LaravelWamp\Exceptions\SerializationException;

/**
 * JSON serializer implementation for WAMP protocol messages.
 *
 * Implements the SerializerContract interface utilizing PHP's native JSON functions 
 * to encode and decode WAMP message arrays, enforcing strict error handling via exceptions 
 * and declaring the standard `wamp.2.json` subprotocol identifier.
 */
class JsonSerializer implements SerializerContract
{
    /**
     * Serialize a WAMP message array into a JSON-encoded string.
     *
     * @param  array<mixed>  $message  The message array to encode.
     * @return string The JSON-encoded string representation.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\SerializationException If encoding fails.
     */
    public function serialize(array $message): string
    {
        try {
            $encoded = json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw new SerializationException(
                "Failed to serialize WAMP message to JSON: {$e->getMessage()}",
                previous: $e,
            );
        }

        return $encoded;
    }

    /**
     * Deserialize a JSON-encoded string into a WAMP message array.
     *
     * @param  string  $raw  The raw JSON string received from the transport.
     * @return array<mixed> The decoded message array.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\SerializationException If decoding fails or the result is not an array.
     */
    public function deserialize(string $raw): array
    {
        try {
            $decoded = json_decode($raw, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new SerializationException(
                "Failed to deserialize WAMP message from JSON: {$e->getMessage()}",
                previous: $e,
            );
        }

        if (!is_array($decoded)) {
            throw new SerializationException('The deserialized WAMP message is not a valid array.');
        }

        return $decoded;
    }

    /**
     * Get the WAMP subprotocol identifier for JSON.
     *
     * @return string The subprotocol string ('wamp.2.json').
     */
    public function subprotocol(): string
    {
        return 'wamp.2.json';
    }
}