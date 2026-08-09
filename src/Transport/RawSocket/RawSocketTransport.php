<?php

namespace Hermod\LaravelWamp\Transport\RawSocket;

use Amp\Socket\ConnectContext;
use Amp\Socket\Socket;
use Hermod\LaravelWamp\Contracts\SerializerContract;
use Hermod\LaravelWamp\Contracts\TransportContract;
use Hermod\LaravelWamp\Exceptions\TransportException;
use Throwable;

/**
 * RawSocket transport layer implementation for WAMP using AMPHP sockets.
 *
 * Implements the TransportContract interface, handling asynchronous TCP or Unix domain socket 
 * connections, performing RawSocket protocol handshakes, encoding frames, and managing 
 * frame streams with built-in control-frame (ping/pong) handling.
 */
class RawSocketTransport implements TransportContract
{
    /** @var Socket|null The underlying active AMP socket instance. */
    private ?Socket $socket = null;

    /** @var int Maximum negotiated message size allowed by the router. */
    private int $maxMessageSize = 0;

    /**
     * Create a new RawSocketTransport instance.
     *
     * @param  \Hermod\LaravelWamp\Contracts\SerializerContract  $serializer  The protocol serializer implementation.
     * @param  string  $url  The socket connection URL (e.g., 'tcp://127.0.0.1:8080' or 'unix:///path/to/socket').
     * @param  float  $connectTimeout  The connection timeout threshold in seconds.
     */
    public function __construct(
        private readonly SerializerContract $serializer,
        private readonly string $url,
        private readonly float $connectTimeout = 10.0,
    ) {
    }

    // -------------------------------------------------------------------------
    // Connection Management
    // -------------------------------------------------------------------------

    /**
     * Establish the socket connection and perform the WAMP RawSocket handshake sequence.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If connection or handshake fails.
     */
    public function connect(): void
    {
        if ($this->isConnected()) {
            return;
        }

        try {
            $context = (new ConnectContext)
                ->withConnectTimeout((int) ($this->connectTimeout * 1000));

            // Supports both TCP (tcp://host:port) and Unix domain sockets (unix:///path/to/socket)
            $this->socket = \Amp\Socket\connect($this->url, $context);

            $this->performHandshake();
        } catch (TransportException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new TransportException(
                "Failed to connect to WAMP router via RawSocket on '{$this->url}': {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Close the active socket connection and reset internal state parameters.
     */
    public function close(): void
    {
        if (!$this->isConnected()) {
            return;
        }

        try {
            $this->socket?->close();
        } catch (Throwable) {
            // Suppress errors during closure
        } finally {
            $this->socket = null;
            $this->maxMessageSize = 0;
        }
    }

    /**
     * Determine whether an active, unclosed socket connection exists.
     *
     * @return bool True if connected, false otherwise.
     */
    public function isConnected(): bool
    {
        return $this->socket !== null
            && !$this->socket->isClosed();
    }

    // -------------------------------------------------------------------------
    // Input / Output Operations
    // -------------------------------------------------------------------------

    /**
     * Encapsulate a message payload into a RawSocket frame and write it across the socket.
     *
     * @param  string  $data  The serialized message payload string.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If not connected or transmission fails.
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
                "Error sending RawSocket message: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Read and decode an incoming RawSocket frame, transparently processing control frames (ping/pong).
     *
     * @return string The payload string of the received regular WAMP message frame.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If not connected or reading/decoding fails.
     */
    public function receive(): string
    {
        $this->ensureConnected();

        try {
            // Read the 4-byte header first
            $header = $this->readExact(4);

            if ($header === null) {
                $this->socket = null;
                throw new TransportException(
                    'RawSocket connection closed by router during read.',
                );
            }

            $frame = RawSocketFrame::decodeHeader($header);

            // Handle transport-level ping/pong frames
            if ($frame['type'] === RawSocketFrame::TYPE_PING) {
                $pingPayload = $frame['length'] > 0
                    ? $this->readExact($frame['length'])
                    : '';

                // Respond automatically with a pong frame
                $this->socket->write(RawSocketFrame::pong($pingPayload ?? ''));

                // Recurse to read the next frame in sequence
                return $this->receive();
            }

            if ($frame['type'] === RawSocketFrame::TYPE_PONG) {
                // Pong received — ignore payload and read the next frame
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
                    'RawSocket connection closed while reading message payload.',
                );
            }

            return $payload;
        } catch (TransportException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->socket = null;
            throw new TransportException(
                "Error receiving RawSocket message: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    // -------------------------------------------------------------------------
    // Internal Helpers
    // -------------------------------------------------------------------------

    /**
     * Execute the RawSocket connection handshake sequence with the router.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If handshake negotiation fails.
     */
    private function performHandshake(): void
    {
        // Send the 4-byte client handshake frame
        $handshake = RawSocketHandshake::build($this->serializer->subprotocol());
        $this->socket->write($handshake);

        // Read the 4-byte response frame from the router
        $response = $this->readExact(4);

        if ($response === null) {
            throw new TransportException(
                'The router closed the connection during the RawSocket handshake.',
            );
        }

        $result = RawSocketHandshake::parse($response);

        $this->maxMessageSize = $result['max_message_size'];
    }

    /**
     * Read an exact number of bytes from the socket, blocking until the buffer is filled.
     *
     * @param  int  $length  The exact number of bytes to read.
     * @return string|null The read string buffer, or null if connection closed prematurely.
     */
    private function readExact(int $length): ?string
    {
        $buffer = '';
        $remaining = $length;

        while ($remaining > 0) {
            $chunk = $this->socket->read(limit: $remaining);

            if ($chunk === null) {
                return null; // Connection closed
            }

            $buffer .= $chunk;
            $remaining -= strlen($chunk);
        }

        return $buffer;
    }

    /**
     * Assert that a valid socket connection is currently active.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\TransportException If no active connection exists.
     */
    private function ensureConnected(): void
    {
        if (!$this->isConnected()) {
            throw new TransportException(
                'No active RawSocket connection. Call connect() before sending or receiving messages.',
            );
        }
    }
}