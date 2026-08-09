<?php

namespace Hermod\LaravelWamp\Serializer;

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
use CBOR\Tag\NegativeBigIntegerTag;
use CBOR\Tag\UnsignedBigIntegerTag;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Hermod\LaravelWamp\Contracts\SerializerContract;
use Hermod\LaravelWamp\Exceptions\SerializationException;
use Throwable;

/**
 * CBOR (Concise Binary Object Representation) serializer implementation for WAMP.
 *
 * Implements the SerializerContract interface utilizing the `spomky-labs/cbor-php` library 
 * to serialize and deserialize WAMP protocol messages between PHP data types and compact binary CBOR.
 */
class CborSerializer implements SerializerContract
{
    /**
     * Serialize a WAMP message array into a binary CBOR string.
     *
     * @param  array<mixed>  $message  The message array to serialize.
     * @return string The binary CBOR string representation.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\SerializationException If serialization fails.
     */
    public function serialize(array $message): string
    {
        try {
            $cbor = $this->phpToCbor($message);

            // spomky-labs/cbor-php implements __toString()
            // to return the binary CBOR representation
            return (string) $cbor;
        } catch (SerializationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SerializationException(
                "Failed to serialize WAMP message to CBOR: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Deserialize a binary CBOR string into a WAMP message array.
     *
     * @param  string  $raw  The binary CBOR string received from the transport.
     * @return array<mixed> The decoded message array.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\SerializationException If decoding fails or result is not an array.
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
        } catch (Throwable $e) {
            throw new SerializationException(
                "Failed to deserialize WAMP message from CBOR: {$e->getMessage()}",
                previous: $e,
            );
        }

        if (!is_array($decoded)) {
            throw new SerializationException(
                'The deserialized WAMP message is not a valid array.',
            );
        }

        return $decoded;
    }

    /**
     * Get the WAMP subprotocol identifier for CBOR.
     *
     * @return string The subprotocol string ('wamp.2.cbor').
     */
    public function subprotocol(): string
    {
        return 'wamp.2.cbor';
    }

    // -------------------------------------------------------------------------
    // PHP → CBOR Conversion Helpers
    // -------------------------------------------------------------------------

    /**
     * Convert a PHP native value into a CBORObject.
     *
     * @param  mixed  $value  The PHP value.
     * @return CBORObject The corresponding CBOR object.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\SerializationException If the type is unsupported.
     */
    private function phpToCbor(mixed $value): CBORObject
    {
        return match (true) {
            is_null($value) => NullObject::create(),
            is_bool($value) => $value ? TrueObject::create() : FalseObject::create(),
            is_int($value) => $this->intToCbor($value),
            is_string($value) => TextStringObject::create($value),

            // stdClass → CBOR Map (dictionary)
            $value instanceof \stdClass => $this->mapToCbor((array) $value),

            is_array($value) => $this->arrayToCbor($value),

            default => throw new SerializationException(
                'Unsupported PHP type for CBOR serialization: ' . gettype($value),
            ),
        };
    }

    /**
     * Convert a PHP integer into standard or bignum CBOR representation.
     *
     * @param  int  $value  The integer value.
     * @return CBORObject The CBOR integer or bignum tag object.
     */
    private function intToCbor(int $value): CBORObject
    {
        if ($value >= 0) {
            // UnsignedIntegerObject only supports up to 0xFFFFFFFF (32-bit)
            // For larger values, we use CBOR Tag 2 (Positive Bignum)
            if ($value <= 0xFFFF_FFFF) {
                return UnsignedIntegerObject::create($value);
            }

            return $this->createPositiveBigInt($value);
        }

        $absValue = abs($value) - 1;

        if ($absValue <= 0xFFFF_FFFF) {
            return NegativeIntegerObject::create($value);
        }

        return $this->createNegativeBigInt($value);
    }

    /**
     * Create a CBOR positive big integer tag object.
     *
     * @param  int  $value  The large integer value.
     * @return CBORObject The positive bignum tag object.
     */
    private function createPositiveBigInt(int $value): CBORObject
    {
        $hex = dechex($value);
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        return UnsignedBigIntegerTag::create(
            ByteStringObject::create(hex2bin($hex))
        );
    }

    /**
     * Create a CBOR negative big integer tag object.
     *
     * @param  int  $value  The large negative integer value.
     * @return CBORObject The negative bignum tag object.
     */
    private function createNegativeBigInt(int $value): CBORObject
    {
        $hex = dechex(abs($value) - 1);
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        return NegativeBigIntegerTag::create(
            ByteStringObject::create(hex2bin($hex))
        );
    }

    /**
     * Convert a PHP array into either a CBOR list or map based on whether it is a list.
     *
     * @param  array<mixed>  $value  The array.
     * @return CBORObject The CBOR ListObject or MapObject.
     */
    private function arrayToCbor(array $value): CBORObject
    {
        // array_is_list → true if keys are 0,1,2,... → CBOR Array []
        // otherwise associative array → CBOR Map {}
        if (array_is_list($value)) {
            return $this->listToCbor($value);
        }

        return $this->mapToCbor($value);
    }

    /**
     * Convert a sequential PHP array into a CBOR ListObject.
     *
     * @param  array<mixed>  $value  The sequential array.
     * @return ListObject The CBOR list object.
     */
    private function listToCbor(array $value): ListObject
    {
        $list = ListObject::create();

        foreach ($value as $item) {
            $list->add($this->phpToCbor($item));
        }

        return $list;
    }

    /**
     * Convert an associative PHP array into a CBOR MapObject.
     *
     * @param  array<mixed>  $value  The associative array.
     * @return MapObject The CBOR map object.
     */
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
    // CBOR → PHP Conversion Helpers
    // -------------------------------------------------------------------------

    /**
     * Recursively convert a CBORObject into native PHP types.
     *
     * @param  CBORObject  $object  The decoded CBOR object.
     * @return mixed The native PHP value.
     */
    private function cborToPhp(CBORObject $object): mixed
    {
        return match (true) {
            $object instanceof NullObject => null,
            $object instanceof TrueObject => true,
            $object instanceof FalseObject => false,

            $object instanceof UnsignedIntegerObject => (int) $object->normalize(),
            $object instanceof NegativeIntegerObject => (int) $object->normalize(),

            // Large integers — Tag 2 and Tag 3 bignums
            $object instanceof UnsignedBigIntegerTag => (int) $object->normalize(),
            $object instanceof NegativeBigIntegerTag => (int) $object->normalize(),

            $object instanceof TextStringObject => (string) $object->normalize(),
            $object instanceof ByteStringObject => (string) $object->normalize(),

            $object instanceof ListObject => $this->cborListToPhp($object),
            $object instanceof MapObject => $this->cborMapToPhp($object),

            default => $object->normalize(),
        };
    }

    /**
     * Convert a CBOR ListObject into a PHP array.
     *
     * @param  ListObject  $list  The CBOR list.
     * @return array<mixed> The converted PHP array.
     */
    private function cborListToPhp(ListObject $list): array
    {
        $result = [];

        foreach ($list as $item) {
            $result[] = $this->cborToPhp($item);
        }

        return $result;
    }

    /**
     * Convert a CBOR MapObject into a PHP associative array.
     *
     * @param  MapObject  $map  The CBOR map.
     * @return array<string, mixed> The converted PHP associative array.
     */
    private function cborMapToPhp(MapObject $map): array
    {
        $result = [];

        foreach ($map as $item) {
            // MapObject iterates over MapItem instances — extract key and value
            /** @var \CBOR\MapItem $item */
            $key = $this->cborToPhp($item->getKey());
            $value = $this->cborToPhp($item->getValue());

            $result[(string) $key] = $value;
        }

        return $result;
    }
}