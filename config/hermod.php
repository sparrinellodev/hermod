<?php

use Hermod\Serializer\CborSerializer;
use Hermod\Serializer\JsonSerializer;
use Hermod\Serializer\MsgpackSerializer;

return [

    'default' => env('WAMP_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'transport' => 'websocket',
            'url' => env('WAMP_URL', 'ws://localhost:8080/ws'),
            'realm' => env('WAMP_REALM', 'realm1'),
            'serializer' => env('WAMP_SERIALIZER', 'json'),

            // Auth — anonymous di default, nessuna configurazione richiesta
            'auth' => [
                'method' => env('WAMP_AUTH_METHOD', 'anonymous'),
            ],

            // Resilienza
            'reconnect' => [
                'enabled' => env('WAMP_RECONNECT', true),
                'max_attempts' => env('WAMP_RECONNECT_MAX', 5),
                'base_delay' => env('WAMP_RECONNECT_DELAY', 1.0),  // secondi
                'max_delay' => env('WAMP_RECONNECT_MAX_DELAY', 30.0),
                'multiplier' => env('WAMP_RECONNECT_MULTIPLIER', 2.0),
            ],

            // Heartbeat
            'heartbeat' => [
                'enabled' => env('WAMP_HEARTBEAT', true),
                'interval' => env('WAMP_HEARTBEAT_INTERVAL', 30), // secondi
            ],
        ],

        // Esempio connessione con Ticket auth
        'ticket' => [
            'transport' => 'websocket',
            'url' => env('WAMP_URL', 'ws://localhost:8080/ws'),
            'realm' => env('WAMP_REALM', 'realm1'),
            'serializer' => env('WAMP_SERIALIZER', 'json'),
            'auth' => [
                'method' => 'ticket',
                'authid' => env('WAMP_AUTH_ID'),
                'ticket' => env('WAMP_AUTH_TICKET'),
            ],
            'reconnect' => [
                'enabled' => true,
                'max_attempts' => 5,
                'base_delay' => 1.0,
                'max_delay' => 30.0,
                'multiplier' => 2.0,
            ],
            'heartbeat' => [
                'enabled' => true,
                'interval' => 30,
            ],
        ],

        // Esempio connessione con WAMP-CRA auth
        'cra' => [
            'transport' => 'websocket',
            'url' => env('WAMP_URL', 'ws://localhost:8080/ws'),
            'realm' => env('WAMP_REALM', 'realm1'),
            'serializer' => env('WAMP_SERIALIZER', 'json'),
            'auth' => [
                'method' => 'wampcra',
                'authid' => env('WAMP_AUTH_ID'),
                'secret' => env('WAMP_AUTH_SECRET'),
            ],
            'reconnect' => [
                'enabled' => true,
                'max_attempts' => 5,
                'base_delay' => 1.0,
                'max_delay' => 30.0,
                'multiplier' => 2.0,
            ],
            'heartbeat' => [
                'enabled' => true,
                'interval' => 30,
            ],
        ],
    ],

    'serializers' => [
        'json' => JsonSerializer::class,
        'msgpack' => MsgpackSerializer::class,
        'cbor' => CborSerializer::class,
    ],

];
