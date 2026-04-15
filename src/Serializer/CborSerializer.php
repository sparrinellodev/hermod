<?php

namespace Hermod\Serializer;

use CBOR\CBORObject;
use CBOR\Decoder;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\OtherObject\FalseObject;
use CBOR\OtherObject\NullObject;
use CBOR\OtherObject\TrueObject;
use CBOR\Stream\StringStream;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Hermod\Contracts\SerializerContract;
use Hermod\Exceptions\SerializationException;

class CborSerializer implements SerializerContract
{
    public function serialize(array $message): string
    {
        try {
            /**
             * @var \CBOR\CBORObject $cbor 
             */
            $cbor = $this->phpToCbor($message);

            return $cbor->serializeObject();
        } catch (SerializationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new SerializationException(
                "Impossibile serializzare il messaggio WAMP in CBOR: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    public function deserialize(string $raw): array
    {
        try {
            $stream  = StringStream::create($raw);
            $decoder = Decoder::create();
            $object  = $decoder->decode($stream);
            $decoded = $this->cborToPhp($object);
        } catch (SerializationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new SerializationException(
                "Impossibile deserializzare il messaggio WAMP dal CBOR: {$e->getMessage()}",
                previous: $e
            );
        }

        if (!is_array($decoded)) {
            throw new SerializationException(
                'Il messaggio WAMP deserializzato non è un array valido.'
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
            is_null($value)    => NullObject::create(),
            is_bool($value)    => $value ? TrueObject::create() : FalseObject::create(),
            is_int($value)     => $this->intToCbor($value),
            is_string($value)  => TextStringObject::create($value),
            is_array($value)   => $this->arrayToCbor($value),
            default            => throw new SerializationException(
                'Tipo PHP non supportato per la serializzazione CBOR: ' . gettype($value)
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
        // array_is_list → true se chiavi sono 0,1,2,... → CBOR Array
        // altrimenti è una mappa con chiavi stringa → CBOR Map
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
            // Le chiavi WAMP nelle map sono sempre stringhe
            $map->add(
                TextStringObject::create((string) $key),
                $this->phpToCbor($item)
            );
        }

        return $map;
    }

    // -------------------------------------------------------------------------
    // Conversione CBOR → PHP
    // -------------------------------------------------------------------------

    private function cborToPhp(CBORObject $object): mixed
    {
        // normalize() converte ricorsivamente il CBOR object
        // in tipi PHP nativi (int, string, bool, null, array)
        return $object->normalize();
    }
}
