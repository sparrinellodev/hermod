<?php

use Hermod\Serializer\CborSerializer;
use Hermod\Serializer\JsonSerializer;
use Hermod\Serializer\MsgpackSerializer;

return [

    'default' => env('WAMP_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'url' => env('WAMP_URL', 'ws://localhost:8080/ws'),
            'realm' => env('WAMP_REALM', 'realm1'),
            'serializer' => env('WAMP_SERIALIZER', 'json'),
        ],
    ],

    'serializers' => [
        'json' => JsonSerializer::class,
        'msgpack' => MsgpackSerializer::class,
        'cbor' => CborSerializer::class,
    ],

    'options' => [
        'timeout' => env('WAMP_TIMEOUT', 30),
        'reconnect' => env('WAMP_RECONNECT', true),
        'reconnect_delay' => env('WAMP_RECONNECT_DELAY', 1),
        'reconnect_max' => env('WAMP_RECONNECT_MAX', 5),
    ],

];
