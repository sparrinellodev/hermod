<?php

namespace Hermod\LaravelWamp\Message;

use Hermod\LaravelWamp\Exceptions\InvalidMessageException;

/**
 * Represents a standardized WAMP protocol message.
 *
 * Encapsulates the raw payload array received from or sent to a WAMP router, 
 * providing structured access to the message type and individual data indices.
 */
class WampMessage
{
    /**
     * Create a new WampMessage instance.
     *
     * @param  MessageType  $type  The resolved message type enum.
     * @param  array<mixed>  $payload  The full raw message array payload.
     */
    private function __construct(
        public readonly MessageType $type,
        public readonly array $payload,
    ) {
    }

    /**
     * Create a WampMessage instance from a raw array received from the router.
     *
     * @param  array<mixed>  $data  The raw array data stream.
     * @return self
     *
     * @throws \Hermod\LaravelWamp\Exceptions\InvalidMessageException If the message is empty, malformed, or has an unknown type.
     */
    public static function fromArray(array $data): self
    {
        if (empty($data) || !isset($data[0])) {
            throw new InvalidMessageException('Empty or malformed WAMP message received.');
        }

        $type = MessageType::tryFrom((int) $data[0]);

        if ($type === null) {
            throw new InvalidMessageException("Unknown WAMP message type identifier: {$data[0]}");
        }

        return new self($type, $data);
    }

    /**
     * Create a new WampMessage instance to be sent to the router.
     *
     * @param  MessageType  $type  The target message type.
     * @param  mixed  ...$parts  The message-specific payload arguments.
     * @return self
     */
    public static function create(MessageType $type, mixed ...$parts): self
    {
        return new self($type, [$type->value, ...$parts]);
    }

    /**
     * Return the complete raw array payload of the message (including the type ID).
     *
     * @return array<mixed> The raw message array.
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * Access a specific field within the payload by its array index.
     *
     * @param  int  $index  The payload index.
     * @return mixed The value at the given index, or null if it does not exist.
     */
    public function get(int $index): mixed
    {
        return $this->payload[$index] ?? null;
    }

    /**
     * Return the message type enum.
     *
     * @return MessageType The message type.
     */
    public function type(): MessageType
    {
        return $this->type;
    }
}