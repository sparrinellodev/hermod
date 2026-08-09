<?php

namespace Hermod\LaravelWamp\Transport;

use Amp\Websocket\Client\WebsocketConnection;
use Amp\Websocket\Client\WebsocketConnector;
use Amp\Websocket\Client\WebsocketHandshake;
use Amp\Websocket\WebsocketClosedException;
use Hermod\LaravelWamp\Contracts\SerializerContract;
use Hermod\LaravelWamp\Contracts\TransportContract;
use Hermod\LaravelWamp\Exceptions\TransportException;

class WebSocketTransport implements TransportContract
{
    private ?WebsocketConnection $connection = null;

    public function __construct(
        private readonly WebsocketConnector $connector,
        private readonly SerializerContract $serializer,
        private readonly string $url,
    ) {
    }

    // -------------------------------------------------------------------------
    // Connessione
    // -------------------------------------------------------------------------

    public function connect(): void
    {
        if ($this->isConnected()) {
            return;
        }

        try {
            $handshake = $this->buildHandshake();
            $this->connection = $this->connector->connect($handshake);
        } catch (TransportException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new TransportException(
                "Impossibile connettersi al router WAMP su '{$this->url}': {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    public function close(): void
    {
        if (!$this->isConnected()) {
            return;
        }

        try {
            $this->connection?->close();
        } catch (\Throwable) {
            // Ignoriamo errori in chiusura
        } finally {
            $this->connection = null;
        }
    }

    public function isConnected(): bool
    {
        return $this->connection !== null
            && !$this->connection->isClosed();
    }

    // -------------------------------------------------------------------------
    // I/O
    // -------------------------------------------------------------------------

    public function send(string $data): void
    {
        $this->ensureConnected();

        try {
            // JSON usa testo, MessagePack e CBOR usano binario
            if ($this->isBinaryProtocol()) {
                $this->connection->sendBinary($data);
            } else {
                $this->connection->sendText($data);
            }
        } catch (WebsocketClosedException $e) {
            $this->connection = null;
            throw new TransportException(
                'Connessione WebSocket chiusa durante l\'invio del messaggio.',
                previous: $e,
            );
        } catch (\Throwable $e) {
            throw new TransportException(
                "Errore durante l\'invio del messaggio: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    public function receive(): string
    {
        $this->ensureConnected();

        try {
            $message = $this->connection->receive();

            // receive() restituisce null quando la connessione viene chiusa
            if ($message === null) {
                $this->connection = null;
                throw new TransportException(
                    'Connessione WebSocket chiusa dal router durante la ricezione.',
                );
            }

            return $message->buffer();
        } catch (TransportException $e) {
            throw $e;
        } catch (WebsocketClosedException $e) {
            $this->connection = null;
            throw new TransportException(
                'Connessione WebSocket chiusa durante la ricezione del messaggio.',
                previous: $e,
            );
        } catch (\Throwable $e) {
            throw new TransportException(
                "Errore durante la ricezione del messaggio: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildHandshake(): WebsocketHandshake
    {
        return new WebsocketHandshake(
            $this->url,
            // Negozia il subprotocol WAMP corretto (json/msgpack/cbor)
            ['Sec-WebSocket-Protocol' => $this->serializer->subprotocol()],
        );
    }

    private function isBinaryProtocol(): bool
    {
        return in_array(
            $this->serializer->subprotocol(),
            ['wamp.2.msgpack', 'wamp.2.cbor'],
            strict: true,
        );
    }

    private function ensureConnected(): void
    {
        if (!$this->isConnected()) {
            throw new TransportException(
                'Nessuna connessione WebSocket attiva. Chiamare connect() prima di inviare messaggi.',
            );
        }
    }
}
