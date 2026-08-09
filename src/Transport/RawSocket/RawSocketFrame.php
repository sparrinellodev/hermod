<?php

namespace Hermod\LaravelWamp\Transport\RawSocket;

use Hermod\LaravelWamp\Exceptions\TransportException;

/**
 * Handles encoding and decoding of WAMP RawSocket transport protocol frames.
 *
 * Implements the WAMP RawSocket framing specification, which uses a 4-byte header 
 * containing message type bits and a 24-bit big-endian length descriptor, 
 * followed by regular message payloads or protocol control ping/pong frames.
 */
class RawSocketFrame
{
    /** @var int Regular WAMP message frame type. */
    public const TYPE_REGULAR = 0;

    /** @var int Ping control frame type. */
    public const TYPE_PING = 1;

    /** @var int Pong control frame type. */
    public const TYPE_PONG = 2;

    /**
     * Encode a payload into a binary RawSocket frame for transmission.
     *
     * Frame layout structure:
     * - Byte 0:    Bits 7-4 reserved, bits 3-0 = message type
     * - Byte 1-3:  Payload length (24-bit big-endian integer)
     * - Byte 4+:   Payload data
     *
     * @param  string  $payload  The serialized message payload string.
     * @param  int  $type  The frame type (TYPE_REGULAR, TYPE_PING, or TYPE_PONG).
     * @return string The fully packed binary frame.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If the payload exceeds the 24-bit length limit (~16MB).
     */
    public static function encode(string $payload, int $type = self::TYPE_REGULAR): string
    {
        $length = strlen($payload);

        if ($length > 0xFFFFFF) {
            throw new TransportException(
                "Payload too large for RawSocket: {$length} bytes. " .
                'Maximum allowed is ' . 0xFFFFFF . ' bytes.',
            );
        }

        // Header: 4 bytes
        // Byte 0: Message type (low nibble)
        // Byte 1-3: Length in big-endian (24-bit)
        $header = pack('C', $type & 0x0F)
            . pack('C', ($length >> 16) & 0xFF)
            . pack('C', ($length >> 8) & 0xFF)
            . pack('C', $length & 0xFF);

        return $header . $payload;
    }

    /**
     * Decode a 4-byte RawSocket frame header.
     *
     * @param  string  $header  The 4-byte raw header string.
     * @return array{length: int, type: int} An array containing the decoded frame 'type' and payload 'length'.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If the header length is not exactly 4 bytes.
     */
    public static function decodeHeader(string $header): array
    {
        if (strlen($header) !== 4) {
            throw new TransportException(
                'Invalid RawSocket frame header: expected 4 bytes, ' .
                'received ' . strlen($header) . '.',
            );
        }

        $bytes = unpack('C4', $header);

        $type = $bytes[1] & 0x0F;
        $length = ($bytes[2] << 16) | ($bytes[3] << 8) | $bytes[4];

        return [
            'type' => $type,
            'length' => $length,
        ];
    }

    /**
     * Generate a ping control frame with an optional payload.
     *
     * @param  string  $payload  Optional ping payload string.
     * @return string The encoded ping frame.
     */
    public static function ping(string $payload = ''): string
    {
        return self::encode($payload, self::TYPE_PING);
    }

    /**
     * Generate a pong control frame with an optional payload.
     *
     * @param  string  $payload  Optional pong payload string.
     * @return string The encoded pong frame.
     */
    public static function pong(string $payload = ''): string
    {
        return self::encode($payload, self::TYPE_PONG);
    }
}