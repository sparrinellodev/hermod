<?php

namespace Hermod\Transport\RawSocket;

use Hermod\Exceptions\TransportException;

class RawSocketFrame
{
    // Tipi di frame RawSocket
    public const TYPE_REGULAR = 0;

    public const TYPE_PING = 1;

    public const TYPE_PONG = 2;

    /**
     * Costruisce un frame RawSocket da inviare.
     *
     * Struttura frame:
     * - Byte 0:    bits 7-4 reserved, bits 3-0 = message type
     * - Byte 1-3:  lunghezza payload (24 bit big-endian)
     * - Byte 4+:   payload
     *
     * @throws TransportException
     */
    public static function encode(string $payload, int $type = self::TYPE_REGULAR): string
    {
        $length = strlen($payload);

        if ($length > 0xFFFFFF) {
            throw new TransportException(
                "Messaggio troppo grande per RawSocket: {$length} byte. ".
                    'Massimo consentito: '. 0xFFFFFF .' byte.',
            );
        }

        // Header: 4 byte
        // Byte 0: tipo messaggio (nibble basso)
        // Byte 1-3: lunghezza in big-endian (24 bit)
        $header = pack('C', $type & 0x0F)
            .pack('C', ($length >> 16) & 0xFF)
            .pack('C', ($length >> 8) & 0xFF)
            .pack('C', $length & 0xFF);

        return $header.$payload;
    }

    /**
     * Summary of decodeHeader
     *
     * @return array{length: int, type: int}
     *
     * @throws TransportException
     */
    public static function decodeHeader(string $header): array
    {
        if (strlen($header) !== 4) {
            throw new TransportException(
                'Header frame RawSocket invalido: attesi 4 byte, '.
                    'ricevuti '.strlen($header).'.',
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
     * Summary of ping
     */
    public static function ping(string $payload = ''): string
    {
        return self::encode($payload, self::TYPE_PING);
    }

    /**
     * Summary of pong
     */
    public static function pong(string $payload = ''): string
    {
        return self::encode($payload, self::TYPE_PONG);
    }
}
