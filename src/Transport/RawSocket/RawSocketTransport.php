<?php

namespace Hermod\Transport\RawSocket;

use Amp\Socket\ConnectContext;
use Amp\Socket\Socket;
use Hermod\Contracts\SerializerContract;
use Hermod\Contracts\TransportContract;
use Hermod\Exceptions\TransportException;
use Throwable;

class RawSocketTransport implements TransportContract
{
    private ?Socket $socket = null;

    private int $maxMessageSize = 0;

    /**
     * Summary of __construct
     */
    public function __construct(
        private readonly SerializerContract $serializer,
        private readonly string $url,
        private readonly float $connectTimeout = 10.0,
    ) {}

    // -------------------------------------------------------------------------
    // Connessione
    // -------------------------------------------------------------------------

    /**
     * Summary of connect
     *
     * @throws TransportException
     */
    public function connect(): void
    {
        if ($this->isConnected()) {
            return;
        }

        try {
            $context = (new ConnectContext)
                ->withConnectTimeout((int) ($this->connectTimeout * 1000));

            // Supporta sia TCP (tcp://host:port) che Unix (unix:///path/to/socket)
            $this->socket = \Amp\Socket\connect($this->url, $context);

            $this->performHandshake();
        } catch (TransportException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new TransportException(
                "Impossibile connettersi al router WAMP via RawSocket su '{$this->url}': {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Summary of close
     */
    public function close(): void
    {
        if (! $this->isConnected()) {
            return;
        }

        try {
            $this->socket?->close();
        } catch (Throwable) {
            // ignoriamo errori in chiusura
        } finally {
            $this->socket = null;
            $this->maxMessageSize = 0;
        }
    }

    public function isConnected(): bool
    {
        return $this->socket !== null
            && ! $this->socket->isClosed();
    }

    // -------------------------------------------------------------------------
    // I/O
    // -------------------------------------------------------------------------

    /**
     * Summary of send
     *
     * @throws TransportException
     */
    public function send(string $data): void
    {
        $this->ensureConnected();

        try {
            $frame = RawSocketFrame::encode($data);
            $this->socket->write($frame);
        } catch (TransportException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->socket = null;
            throw new TransportException(
                "Errore durante l'invio del messaggio RawSocket: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Summary of receive
     *
     * @throws TransportException
     */
    public function receive(): string
    {
        $this->ensureConnected();

        try {
            // Leggo prima i 4 byte dell'header
            $header = $this->readExact(4);

            if ($header === null) {
                $this->socket = null;
                throw new TransportException(
                    'Connessione RawSocket chiusa dal router durante la ricezione.',
                );
            }

            $frame = RawSocketFrame::decodeHeader($header);

            // Gestione ping/pong a livello transport
            if ($frame['type'] === RawSocketFrame::TYPE_PING) {
                $pingPayload = $frame['length'] > 0
                    ? $this->readExact($frame['length'])
                    : '';

                // Rispondo con pong
                $this->socket->write(RawSocketFrame::pong($pingPayload ?? ''));

                // Ricorsione — leggo il prossimo frame
                return $this->receive();
            }

            if ($frame['type'] === RawSocketFrame::TYPE_PONG) {
                // Pong ricevuto — ignoro e leggo il prossimo frame
                if ($frame['length'] > 0) {
                    $this->readExact($frame['length']);
                }

                return $this->receive();
            }

            if ($frame['length'] === 0) {
                return '';
            }

            $payload = $this->readExact($frame['length']);

            if ($payload === null) {
                $this->socket = null;
                throw new TransportException(
                    'Connessione RawSocket chiusa durante la lettura del payload.',
                );
            }

            return $payload;
        } catch (TransportException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->socket = null;
            throw new TransportException(
                "Errore durante la ricezione del messaggio RawSocket: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Summary of performHandshake
     *
     * @throws TransportException
     */
    private function performHandshake(): void
    {
        // Inviamo i 4 byte di handshake
        $handshake = RawSocketHandshake::build($this->serializer->subprotocol());
        $this->socket->write($handshake);

        // Leggiamo i 4 byte di risposta
        $response = $this->readExact(4);

        if ($response === null) {
            throw new TransportException(
                'Il router ha chiuso la connessione durante l\'handshake RawSocket.',
            );
        }

        $result = RawSocketHandshake::parse($response);

        $this->maxMessageSize = $result['max_message_size'];
    }

    /**
     * Summary of readExact
     */
    private function readExact(int $length): ?string
    {
        $buffer = '';
        $remaining = $length;

        while ($remaining > 0) {
            $chunk = $this->socket->read(limit: $remaining);

            if ($chunk === null) {
                return null; // connessione chiusa
            }

            $buffer .= $chunk;
            $remaining -= strlen($chunk);
        }

        return $buffer;
    }

    /**
     * Summary of ensureConnected
     *
     * @throws TransportException
     */
    private function ensureConnected(): void
    {
        if (! $this->isConnected()) {
            throw new TransportException(
                'Nessuna connessione RawSocket attiva. Chiamare connect() prima di inviare messaggi.',
            );
        }
    }
}
