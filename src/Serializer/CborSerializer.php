<?php

namespace Hermod\Serializer;

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

/**
 * Serializzatore CBOR per messaggi WAMP.
 * * Questa classe gestisce la conversione tra array nativi PHP e il formato binario CBOR,
 * implementando le specifiche necessarie per il protocollo WAMP.
 */
class CborSerializer implements SerializerContract
{
    /**
     * Serializza un array PHP in una stringa binaria CBOR.
     *
     * @param  array<mixed>  $message  Il messaggio da serializzare.
     * @return string La rappresentazione binaria CBOR.
     *
     * @throws SerializationException Se avviene un errore durante la codifica.
     */
    public function serialize(array $message): string
    {
        try {
            $cbor = $this->phpToCbor($message);

            return (string) $cbor;
        } catch (SerializationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new SerializationException(
                "Impossibile serializzare il messaggio WAMP in CBOR: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Deserializza una stringa binaria CBOR in un array PHP.
     *
     * @param  string  $raw  I dati binari CBOR.
     * @return array<mixed> Il messaggio deserializzato.
     *
     * @throws SerializationException Se i dati non sono validi o non rappresentano un array.
     */
    public function deserialize(string $raw): array
    {
        try {
            $stream = StringStream::create($raw);
            $decoder = Decoder::create();
            $object = $decoder->decode($stream);

            $decoded = $this->cborToPhp($object);
        } catch (SerializationException $e) {
            throw $e;
        } catch (\Throwable $e) {
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

    /**
     * Identificativo del sottoprotocollo WAMP.
     */
    public function subprotocol(): string
    {
        return 'wamp.2.cbor';
    }

    /**
     * Converte ricorsivamente valori PHP in oggetti CBOR.
     *
     * @throws SerializationException
     */
    private function phpToCbor(mixed $value): CBORObject
    {
        return match (true) {
            is_null($value) => NullObject::create(),
            is_bool($value) => $value ? TrueObject::create() : FalseObject::create(),
            is_int($value) => $this->intToCbor($value),
            is_string($value) => TextStringObject::create($value),
            is_array($value) => $this->arrayToCbor($value),
            default => throw new SerializationException(
                'Tipo PHP non supportato per la serializzazione CBOR: '.gettype($value),
            ),
        };
    }

    /**
     * Converte un array PHP in ListObject o MapObject a seconda della struttura.
     *
     * @param  array<mixed>  $value
     */
    private function arrayToCbor(array $value): CBORObject
    {
        if (array_is_list($value)) {
            $list = ListObject::create();
            foreach ($value as $item) {
                $list->add($this->phpToCbor($item));
            }

            return $list;
        }

        $map = MapObject::create();
        foreach ($value as $key => $item) {
            $cborKey = is_int($key)
                ? $this->intToCbor($key)
                : TextStringObject::create((string) $key);

            $map->add($cborKey, $this->phpToCbor($item));
        }

        return $map;
    }

    /**
     * Gestisce la creazione di interi con segno o senza segno.
     */
    private function intToCbor(int $value): CBORObject
    {
        return $value >= 0
            ? UnsignedIntegerObject::create($value)
            : NegativeIntegerObject::create($value);
    }

    /**
     * Converte ricorsivamente oggetti CBOR in tipi nativi PHP.
     */
    private function cborToPhp(CBORObject $object): mixed
    {
        return match (true) {
            $object instanceof UnsignedIntegerObject,
            $object instanceof NegativeIntegerObject => (int) $object->getValue(),

            $object instanceof TextStringObject => $object->getValue(),

            $object instanceof TrueObject => true,
            $object instanceof FalseObject => false,
            $object instanceof NullObject => null,

            $object instanceof ListObject => array_map(
                fn ($item) => $this->cborToPhp($item),
                iterator_to_array($object),
            ),

            $object instanceof MapObject => $this->mapFromCbor($object),

            default => $object->normalize(),
        };
    }

    /**
     * Trasforma un MapObject CBOR in un array associativo PHP.
     *
     * @return array<mixed>
     */
    private function mapFromCbor(MapObject $map): array
    {
        $result = [];
        foreach ($map as $item) {
            /** @var MapItem $item */
            $key = $this->cborToPhp($item->getKey());
            $value = $this->cborToPhp($item->getValue());

            $result[$key] = $value;
        }

        return $result;
    }
}
