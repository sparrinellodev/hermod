<?php

namespace Hermod\Transport\RawSocket;

use Hermod\Exceptions\TransportException;

class RawSocketHandshake
{
    // Byte che identifica il protocollo RawSocket WAMP
    private const MAGIC = 0x7F;

    // Max message length codes (2^(9+n) bytes)
    private const MAX_LENGTH_CODE = 15; // 2^24 = 16MB

    // Serializer codes
    private const SERIALIZER_JSON = 1;

    private const SERIALIZER_MSGPACK = 2;

    private const SERIALIZER_CBOR = 3;

    private static array $serializerMap = [
        'wamp.2.json' => self::SERIALIZER_JSON,
        'wamp.2.msgpack' => self::SERIALIZER_MSGPACK,
        'wamp.2.cbor' => self::SERIALIZER_CBOR,
    ];

    /**
     * Costruisce i 4 byte di handshake da inviare al router.
     *
     * @throws TransportException
     */
    public static function build(string $subprotocol): string
    {
        $serializerCode = self::$serializerMap[$subprotocol]
            ?? throw new TransportException(
                "Serializzatore non supportato per RawSocket: '{$subprotocol}'. ".
                    'Supportati: wamp.2.json, wamp.2.msgpack, wamp.2.cbor',
            );

        // Byte 0: Magic
        // Byte 1: max length (4 bit high) + serializer (4 bit low)
        // Byte 2: reserved
        // Byte 3: reserved
        $byte1 = (self::MAX_LENGTH_CODE << 4) | $serializerCode;

        return pack('C4', self::MAGIC, $byte1, 0x00, 0x00);
    }

    /**
     * Verifica e analizza i 4 byte di risposta del router.
     *
     * @return array{max_message_size: int, subprotocol: mixed}
     *
     * @throws TransportException
     */
    public static function parse(string $response): array
    {
        if (strlen($response) !== 4) {
            throw new TransportException(
                'Risposta handshake RawSocket invalida: attesi 4 byte, '.
                    'ricevuti '.strlen($response).'.',
            );
        }

        $bytes = unpack('C4', $response);

        // Verifica byte identificativo
        if ($bytes[1] !== self::MAGIC) {
            throw new TransportException(
                sprintf(
                    'Magic byte RawSocket non valido: atteso 0x7F, ricevuto 0x%02X.',
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
                "Router RawSocket ha rifiutato la connessione: {$errorMessage}",
            );
        }

        $maxMessageSize = 2 ** (9 + $maxLengthCode);

        $subprotocol = array_flip(self::$serializerMap)[$serializerCode]
            ?? throw new TransportException(
                "Codice serializzatore sconosciuto nella risposta RawSocket: {$serializerCode}",
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
     * Summary of parseError
     */
    private static function parseError(int $errorCode): string
    {
        return match ($errorCode) {
            0 => 'Errore generico',
            1 => 'Serializzatore non supportato',
            2 => 'Dimensione massima messaggio non accettata',
            3 => 'Router in uso',
            default => "Codice errore sconosciuto: {$errorCode}",
        };
    }
}
