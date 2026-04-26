<?php

namespace Hermod\Serializer;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\Decoder;
use CBOR\ListObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\OtherObject\FalseObject;
use CBOR\OtherObject\NullObject;
use CBOR\OtherObject\TrueObject;
use CBOR\StringStream;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Hermod\Contracts\SerializerContract;
use Hermod\Exceptions\SerializationException;
use Throwable;

class CborSerializer implements SerializerContract
{
    public function serialize(array $message): string
    {
        try {
            $cbor = $this->phpToCbor($message);

            // spomky-labs/cbor-php implementa __toString()
            // per restituire la rappresentazione binaria CBOR
            return (string) $cbor;
        } catch (SerializationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SerializationException(
                "Impossibile serializzare il messaggio WAMP in CBOR: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    public function deserialize(string $raw): array
    {
        try {
            $stream = StringStream::create($raw);
            $decoder = Decoder::create();
            $object = $decoder->decode($stream);
            $decoded = $this->cborToPhp($object);
        } catch (SerializationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SerializationException(
                "Impossibile deserializzare il messaggio WAMP dal CBOR: {$e->getMessage()}",
                previous: $e,
            );
        }

        if (! is_array($decoded)) {
            throw new SerializationException(
                'Il messaggio WAMP deserializzato non è un array valido.',
            );
        }

        return $decoded;
    }

    public function subprotocol(): string
    {
        return 'wamp.2.cbor';
    }

    // -------------------------------------------------------------------------
    // Conversione PHP → CBOR
    // -------------------------------------------------------------------------

    private function phpToCbor(mixed $value): CBORObject
    {
        return match (true) {
            is_null($value) => NullObject::create(),
            is_bool($value) => $value ? TrueObject::create() : FalseObject::create(),
            is_int($value) => $this->intToCbor($value),
            is_string($value) => TextStringObject::create($value),

            // stdClass → CBOR Map (dizionario)
            $value instanceof \stdClass => $this->mapToCbor((array) $value),

            is_array($value) => $this->arrayToCbor($value),

            default => throw new SerializationException(
                'Tipo PHP non supportato per la serializzazione CBOR: ' . gettype($value),
            ),
        };
    }

    private function intToCbor(int $value): CBORObject
    {
        if ($value >= 0) {
            return UnsignedIntegerObject::create($value);
        }

        return NegativeIntegerObject::create($value);
    }

    private function arrayToCbor(array $value): CBORObject
    {
        // array_is_list → true se chiavi sono 0,1,2,... → CBOR Array []
        // altrimenti array associativo → CBOR Map {}
        if (array_is_list($value)) {
            return $this->listToCbor($value);
        }

        return $this->mapToCbor($value);
    }

    private function listToCbor(array $value): ListObject
    {
        $list = ListObject::create();

        foreach ($value as $item) {
            $list->add($this->phpToCbor($item));
        }

        return $list;
    }

    private function mapToCbor(array $value): MapObject
    {
        $map = MapObject::create();

        foreach ($value as $key => $item) {
            $map->add(
                TextStringObject::create((string) $key),
                $this->phpToCbor($item),
            );
        }

        return $map;
    }

    // -------------------------------------------------------------------------
    // Conversione CBOR → PHP
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // Conversione CBOR → PHP
    // -------------------------------------------------------------------------

    private function cborToPhp(CBORObject $object): mixed
    {
        // Gestiamo ogni tipo CBOR esplicitamente
        // invece di usare normalize() che converte gli interi in stringhe

        return match (true) {
            $object instanceof NullObject  => null,
            $object instanceof TrueObject  => true,
            $object instanceof FalseObject => false,

            $object instanceof UnsignedIntegerObject   => (int) $object->normalize(),
            $object instanceof NegativeIntegerObject   => (int) $object->normalize(),

            $object instanceof TextStringObject        => (string) $object->normalize(),
            $object instanceof ByteStringObject        => (string) $object->normalize(),

            $object instanceof ListObject              => $this->cborListToPhp($object),
            $object instanceof MapObject               => $this->cborMapToPhp($object),

            default => $object->normalize(),
        };
    }

    private function cborListToPhp(ListObject $list): array
    {
        $result = [];

        foreach ($list as $item) {
            $result[] = $this->cborToPhp($item);
        }

        return $result;
    }

    private function cborMapToPhp(MapObject $map): array
    {
        $result = [];

        foreach ($map as $item) {
        // MapObject itera su MapItem — dobbiamo estrarre chiave e valore
            /** @var \CBOR\MapItem $item */
            $key   = $this->cborToPhp($item->getKey());
            $value = $this->cborToPhp($item->getValue());

            $result[(string) $key] = $value;
        }

        return $result;
    }
}
