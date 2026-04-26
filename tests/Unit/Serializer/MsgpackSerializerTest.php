<?php

use Hermod\Exceptions\SerializationException;
use Hermod\Serializer\MsgpackSerializer;

describe('MsgpackSerializer', function () {

    beforeEach(function () {
        $this->serializer = new MsgpackSerializer;
    });

    it('serializza un array in MessagePack', function () {
        $data = [48, 1, [], 'com.myapp.test', [3, 5]];
        $result = $this->serializer->serialize($data);

        expect($result)
            ->toBeString()
            ->not->toBeEmpty();
    });

    it('deserializza MessagePack in array corretto', function () {
        $original = [48, 1, [], 'com.myapp.test', [3, 5]];
        $packed = $this->serializer->serialize($original);
        $result = $this->serializer->deserialize($packed);

        expect($result)->toBe($original);
    });

    it('è simmetrico — serialize poi deserialize restituisce i dati originali', function () {
        $original = [2, 123456, ['roles' => ['caller' => [], 'callee' => []]]];
        $result = $this->serializer->deserialize(
            $this->serializer->serialize($original),
        );

        expect($result)->toBe($original);
    });

    it('gestisce stringhe unicode correttamente', function () {
        $data = [1, 'realm1', ['authmethods' => ['anonymous'], 'authextra' => []]];
        $packed = $this->serializer->serialize($data);
        $result = $this->serializer->deserialize($packed);

        expect($result[1])->toBe('realm1');
    });

    it('gestisce array vuoti', function () {
        $data = [48, 1, [], 'com.test', [], []];
        $packed = $this->serializer->serialize($data);
        $result = $this->serializer->deserialize($packed);

        expect($result)->toBe($data);
    });

    it('gestisce tipi misti', function () {
        $data = [50, 99, [], [true, false, null, 42, 3.14, 'stringa']];
        $packed = $this->serializer->serialize($data);
        $result = $this->serializer->deserialize($packed);

        expect($result[3][0])->toBeTrue()
            ->and($result[3][1])->toBeFalse()
            ->and($result[3][2])->toBeNull()
            ->and($result[3][3])->toBe(42)
            ->and($result[3][5])->toBe('stringa');
    });

    it('lancia SerializationException per dati non deserializzabili', function () {
        $this->serializer->deserialize('dati-invalidi-non-msgpack');
    })->throws(SerializationException::class);

    it('restituisce il subprotocol corretto', function () {
        expect($this->serializer->subprotocol())->toBe('wamp.2.msgpack');
    });

    it('produce output più compatto di JSON per payload grandi', function () {
        $data = array_fill(0, 100, 'stringa-di-test-abbastanza-lunga');

        $json = json_encode($data);
        $msgpack = $this->serializer->serialize($data);

        expect(strlen($msgpack))->toBeLessThan(strlen($json));
    });

    it('serializza stdClass come mappa MessagePack', function () {
        // stdClass vuoto → mappa vuota MessagePack
        $data = [48, 1, (object) [], 'com.myapp.test', [3, 5], (object) []];
        $packed = $this->serializer->serialize($data);

        // Non deve lanciare eccezioni
        expect($packed)->toBeString()->not->toBeEmpty();
    });

    it('serializza array associativo come mappa MessagePack', function () {
        $data = [2, 123, ['roles' => ['caller' => [], 'callee' => []]]];
        $packed = $this->serializer->serialize($data);
        $result = $this->serializer->deserialize($packed);

        expect($result[2]['roles'])->toHaveKeys(['caller', 'callee']);
    });
});
