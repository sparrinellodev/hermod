<?php

namespace Hermod\Transport;

use Hermod\Contracts\SerializerContract;
use Hermod\Contracts\TransportContract;
use Hermod\Exceptions\TransportException;

class TransportFactory
{
    /**
     * Summary of __construct
     */
    public function __construct(
        private readonly WebSocketTransportFactory $websocketFactory,
        private readonly RawSocketTransportFactory $rawSocketFactory,
    ) {}

    /**
     * Crea il transport corretto in base alla configurazione.
     * Tipi supportati:
     * - 'websocket' → WebSocketTransport (ws:// o wss://)
     * - 'rawsocket' → RawSocketTransport (tcp:// o unix://)
     *
     * @return RawSocket\RawSocketTransport|TransportContract
     */
    public function make(
        string $type,
        string $url,
        SerializerContract $serializer,
    ): TransportContract {
        return match ($type) {
            'websocket' => $this->websocketFactory->make(
                url: $url,
                serializer: $serializer,
            ),
            'rawsocket' => $this->rawSocketFactory->make(
                url: $url,
                serializer: $serializer,
            ),
            default => throw new TransportException(
                "Tipo di transport non supportato: '{$type}'. ".
                    'Valori accettati: websocket, rawsocket',
            ),
        };
    }
}
