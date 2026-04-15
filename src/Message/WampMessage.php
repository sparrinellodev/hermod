<?php

namespace Hermod\Message;

use Hermod\Exceptions\InvalidMessageException;

class WampMessage
{
    private function __construct(
        public readonly MessageType $type,
        public readonly array $payload,
    ) {}

    /**
     * Crea un messaggio da un array raw (ricevuto dal router).
     */
    public static function fromArray(array $data): self
    {
        if (empty($data) || !isset($data[0])) {
            throw new InvalidMessageException('Messaggio WAMP vuoto o malformato.');
        }

        $type = MessageType::tryFrom((int) $data[0]);

        if ($type === null) {
            throw new InvalidMessageException("Tipo di messaggio WAMP sconosciuto: {$data[0]}");
        }

        return new self($type, $data);
    }

    /**
     * Crea un messaggio da costruire (da inviare al router).
     */
    public static function create(MessageType $type, mixed ...$parts): self
    {
        return new self($type, [$type->value, ...$parts]);
    }

    /**
     * Restituisce l'array completo del messaggio (type ID incluso).
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * Accede a un campo specifico del payload per indice.
     */
    public function get(int $index): mixed
    {
        return $this->payload[$index] ?? null;
    }

    /**
     * Restituisce il tipo del messaggio.
     */
    public function type(): MessageType
    {
        return $this->type;
    }
}
