<?php

use Hermod\Serializer\MsgpackSerializer;
use Hermod\Exceptions\SerializationException;

describe('MsgpackSerializer', function () {

    beforeEach(function () {
        if (!extension_loaded('msgpack')) {
            test()->markTestSkipped('Estensione msgpack non installata.');
        }
    });

    it('serializza un array in MessagePack', function () {
        $serializer = new MsgpackSerializer();

        $data = [1, 'hello', true, null];

        $result = $serializer->serialize($data);

        expect($result)->toBeString();
        expect($result)->not->toBeEmpty();
    });

    it('deserializza MessagePack in array', function () {
        $serializer = new MsgpackSerializer();

        $data = [1, 'hello', true, null];

        $encoded = $serializer->serialize($data);
        $decoded = $serializer->deserialize($encoded);

        expect($decoded)->toBe($data);
    });

    it('è simmetrico (serialize → deserialize)', function () {
        $serializer = new MsgpackSerializer();

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
            $serializer->serialize($data)
        );

        expect($result)->toBe($data);
    });

    it('gestisce mappe associative correttamente', function () {
        $serializer = new MsgpackSerializer();

        $data = [
            'type' => 1,
            'procedure' => 'com.test',
            'args' => [1, 2, 3],
        ];

        $result = $serializer->deserialize(
            $serializer->serialize($data)
        );

        expect($result)->toBe($data);
    });

    it('lancia eccezione per payload non valido', function () {
        $serializer = new MsgpackSerializer();

        $invalid = 'not-valid-msgpack';

        expect(fn() => $serializer->deserialize($invalid))
            ->toThrow(SerializationException::class);
    });

    it('restituisce il subprotocol corretto', function () {
        $serializer = new MsgpackSerializer();

        expect($serializer->subprotocol())
            ->toBe('wamp.2.msgpack');
    });
});
