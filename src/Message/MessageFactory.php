<?php

namespace Hermod\Message;

class MessageFactory
{
    // -------------------------------------------------------------------------
    // Session
    // -------------------------------------------------------------------------

    public static function hello(string $realm): WampMessage
    {
        return WampMessage::create(
            MessageType::HELLO,
            $realm,
            [
                'roles' => [
                    'caller' => [
                        'features' => [
                            'progressive_call_results' => false,
                        ],
                    ],
                    'callee' => [
                        'features' => [
                            'progressive_call_invocations' => false,
                        ],
                    ],
                ],
            ]
        );
    }

    public static function goodbye(string $reason = 'wamp.close.normal'): WampMessage
    {
        return WampMessage::create(
            MessageType::GOODBYE,
            (object) [],    // ← oggetto vuoto → {}
            $reason
        );
    }

    // -------------------------------------------------------------------------
    // RPC - Caller
    // -------------------------------------------------------------------------

    public static function call(
        int $requestId,
        string $procedure,
        array $args = [],
        array $kwargs = []
    ): WampMessage {
        return WampMessage::create(
            MessageType::CALL,
            $requestId,
            (object) [],    // ← options → {}
            $procedure,
            $args,
            (object) $kwargs ?: (object) []  // ← kwargs come oggetto se vuoto
        );
    }

    // -------------------------------------------------------------------------
    // RPC - Callee
    // -------------------------------------------------------------------------

    public static function register(int $requestId, string $procedure): WampMessage
    {
        return WampMessage::create(
            MessageType::REGISTER,
            $requestId,
            (object) [],    // ← options → {}
            $procedure
        );
    }

    public static function unregister(int $requestId, int $registrationId): WampMessage
    {
        return WampMessage::create(
            MessageType::UNREGISTER,
            $requestId,
            $registrationId
        );
    }

    public static function yield(
        int $invocationRequestId,
        array $args = [],
        array $kwargs = []
    ): WampMessage {
        return WampMessage::create(
            MessageType::YIELD,
            $invocationRequestId,
            (object) [],    // ← options → {}
            $args,
            (object) $kwargs ?: (object) []
        );
    }

    public static function yieldError(
        int $invocationRequestId,
        string $error,
        array $args = []
    ): WampMessage {
        return WampMessage::create(
            MessageType::ERROR,
            MessageType::INVOCATION->value,
            $invocationRequestId,
            (object) [],    // ← details → {}
            $error,
            $args
        );
    }
}
