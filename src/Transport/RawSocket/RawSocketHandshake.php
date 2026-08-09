<?php

namespace Hermod\LaravelWamp\Transport\RawSocket;

use Hermod\LaravelWamp\Exceptions\TransportException;

/**
 * Handles the 4-byte client/router connection handshake sequence for WAMP RawSocket transport.
 *
 * Encapsulates WAMP RawSocket protocol specifications, mapping serializers to corresponding 
 * protocol codes, packing the initial client handshake frame, and validating router response bytes 
 * for magic indicators, maximum message size limits, and negotiated subprotocols.
 */
class RawSocketHandshake
{
    /** @var int Magic byte identifying the WAMP RawSocket protocol (0x7F). */
    private const MAGIC = 0x7F;

    /** @var int Maximum message length code (value 15 yields $2^{9+15} = 2^{24}$ bytes = 16MB). */
    private const MAX_LENGTH_CODE = 15; // 2^24 = 16MB

    /** @var int Serializer code for JSON. */
    private const SERIALIZER_JSON = 1;

    /** @var int Serializer code for MessagePack. */
    private const SERIALIZER_MSGPACK = 2;

    /** @var int Serializer code for CBOR. */
    private const SERIALIZER_CBOR = 3;

    /** @var array<string, int> Mapping of WAMP subprotocol URIs to RawSocket serializer codes. */
    private static array $serializerMap = [
        'wamp.2.json' => self::SERIALIZER_JSON,
        'wamp.2.msgpack' => self::SERIALIZER_MSGPACK,
        'wamp.2.cbor' => self::SERIALIZER_CBOR,
    ];

    /**
     * Construct the 4-byte client handshake frame to be sent to the WAMP router.
     *
     * @param  string  $subprotocol  The target WAMP subprotocol (e.g., 'wamp.2.json').
     * @return string The 4-byte packed binary handshake string.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If the subprotocol serializer is unsupported.
     */
    public static function build(string $subprotocol): string
    {
        $serializerCode = self::$serializerMap[$subprotocol]
            ?? throw new TransportException(
                "Unsupported serializer for RawSocket: '{$subprotocol}'. " .
                'Supported: wamp.2.json, wamp.2.msgpack, wamp.2.cbor',
            );

        // Byte 0: Magic identifier
        // Byte 1: Max length (high 4 bits) + serializer (low 4 bits)
        // Byte 2: Reserved
        // Byte 3: Reserved
        $byte1 = (self::MAX_LENGTH_CODE << 4) | $serializerCode;

        return pack('C4', self::MAGIC, $byte1, 0x00, 0x00);
    }

    /**
     * Verify and parse the 4-byte handshake response returned by the router.
     *
     * @param  string  $response  The 4-byte binary response string.
     * @return array{max_message_size: int, subprotocol: string} An array containing the parsed max message size and subprotocol.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If the response size, magic byte, or router codes are invalid.
     */
    public static function parse(string $response): array
    {
        if (strlen($response) !== 4) {
            throw new TransportException(
                'Invalid RawSocket handshake response: expected 4 bytes, ' .
                'received ' . strlen($response) . '.',
            );
        }

        $bytes = unpack('C4', $response);

        // Verify protocol magic byte
        if ($bytes[1] !== self::MAGIC) {
            throw new TransportException(
                sprintf(
                    'Invalid RawSocket magic byte: expected 0x7F, received 0x%02X.',
                    $bytes[1],
                ),
            );
        }

        $byte1 = $bytes[2];

        $maxLengthCode = ($byte1 >> 4) & 0x0F;
        $serializerCode = $byte1 & 0x0F;

        if ($maxLengthCode === 0) {
            $errorMessage = self::parseError($serializerCode);
            throw new TransportException(
                "RawSocket router refused connection: {$errorMessage}",
            );
        }

        $maxMessageSize = 2 ** (9 + $maxLengthCode);

        $subprotocol = array_flip(self::$serializerMap)[$serializerCode]
            ?? throw new TransportException(
                "Unknown serializer code in RawSocket response: {$serializerCode}",
            );

        return [
            'max_message_size' => $maxMessageSize,
            'subprotocol' => $subprotocol,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Translate a router refusal error code into a human-readable error description.
     *
     * @param  int  $errorCode  The error code received from the router.
     * @return string The descriptive error message.
     */
    private static function parseError(int $errorCode): string
    {
        return match ($errorCode) {
            0 => 'Generic error',
            1 => 'Unsupported serializer',
            2 => 'Maximum message size not accepted',
            3 => 'Router busy',
            default => "Unknown error code: {$errorCode}",
        };
    }
}