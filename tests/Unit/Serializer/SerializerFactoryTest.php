<?php

use Hermod\Exceptions\SerializationException;
use Hermod\Serializer\CborSerializer;
use Hermod\Serializer\JsonSerializer;
use Hermod\Serializer\MsgpackSerializer;
use Hermod\Serializer\SerializerFactory;

describe('SerializerFactory', function () {

    beforeEach(function () {
        $this->factory = new SerializerFactory([
            'json' => JsonSerializer::class,
            'msgpack' => MsgpackSerializer::class,
            'cbor' => CborSerializer::class,
        ]);
    });

    it('crea un JsonSerializer correttamente', function () {
        $serializer = $this->factory->make('json');

        expect($serializer)->toBeInstanceOf(JsonSerializer::class);
    });

    it('lancia eccezione per driver sconosciuto', function () {
        $this->factory->make('driver-inesistente');
    })->throws(SerializationException::class, 'non supportato');

    it('lancia eccezione per classe inesistente', function () {
        $factory = new SerializerFactory(['fake' => 'Hermod\\Serializer\\FakeSerializer']);
        $factory->make('fake');
    })->throws(SerializationException::class, 'non trovata');
});
