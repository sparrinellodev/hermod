<?php

use Hermod\Exceptions\SerializationException;
use Hermod\Serializer\CborSerializer;

describe('CborSerializer', function () {

    it('serializza un array in CBOR', function () {
        $serializer = new CborSerializer;

        $data = [1, 'hello', true, null];

        $result = $serializer->serialize($data);

        expect($result)->toBeString();
        expect($result)->not->toBeEmpty();
    });

    it('deserializza CBOR in array', function () {
        $serializer = new CborSerializer;

        $data = [1, 'hello', true, null];

        $encoded = $serializer->serialize($data);
        $decoded = $serializer->deserialize($encoded);

        expect($decoded)->toBe($data);
    });

    it('è simmetrico (serialize → deserialize)', function () {
        $serializer = new CborSerializer;

        $data = [
            123,
            'test',
            true,
            null,
            -42,
            ['nested', 1, false],
            ['key' => 'value', 'num' => 10],
        ];

        $result = $serializer->deserialize(
            $serializer->serialize($data),
        );

        expect($result)->toBe($data);
    });

    it('gestisce mappe associative correttamente', function () {
        $serializer = new CborSerializer;

        $data = [
            'type' => 1,
            'procedure' => 'com.test',
            'args' => [1, 2, 3],
        ];

        $result = $serializer->deserialize(
            $serializer->serialize($data),
        );

        expect($result)->toBe($data);
    });

    it('lancia eccezione per tipo non supportato', function () {
        $serializer = new CborSerializer;

        $data = [
            'invalid' => fopen('php://memory', 'r'), // resource → non supportato
        ];

        expect(fn () => $serializer->serialize($data))
            ->toThrow(SerializationException::class);
    });

    it('lancia eccezione per CBOR non valido', function () {
        $serializer = new CborSerializer;

        $invalid = 'not-valid-cbor';

        expect(fn () => $serializer->deserialize($invalid))
            ->toThrow(SerializationException::class);
    });

    it('restituisce il subprotocol corretto', function () {
        $serializer = new CborSerializer;

        expect($serializer->subprotocol())
            ->toBe('wamp.2.cbor');
    });

    it('gestisce interi negativi e positivi', function () {
        $serializer = new CborSerializer;

        $data = [0, 1, -1, 42, -999];

        $result = $serializer->deserialize(
            $serializer->serialize($data),
        );

        expect($result)->toBe($data);
    });
});
