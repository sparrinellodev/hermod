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
            ],
        );
    }

    public static function goodbye(string $reason = 'wamp.close.normal'): WampMessage
    {
        return WampMessage::create(
            MessageType::GOODBYE,
            (object) [],    // ← oggetto vuoto → {}
            $reason,
        );
    }

    // -------------------------------------------------------------------------
    // RPC - Caller
    // -------------------------------------------------------------------------

    public static function call(
        int $requestId,
        string $procedure,
        array $args = [],
        array $kwargs = [],
    ): WampMessage {
        return WampMessage::create(
            MessageType::CALL,
            $requestId,
            (object) [],
            $procedure,
            $args,
            (object) $kwargs ?: (object) [],
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
            $procedure,
        );
    }

    public static function unregister(int $requestId, int $registrationId): WampMessage
    {
        return WampMessage::create(
            MessageType::UNREGISTER,
            $requestId,
            $registrationId,
        );
    }

    public static function yield(
        int $invocationRequestId,
        array $args = [],
        array $kwargs = [],
    ): WampMessage {
        return WampMessage::create(
            MessageType::YIELD,
            $invocationRequestId,
            (object) [],    // ← options → {}
            $args,
            (object) $kwargs ?: (object) [],
        );
    }

    public static function yieldError(
        int $invocationRequestId,
        string $error,
        array $args = [],
    ): WampMessage {
        return WampMessage::create(
            MessageType::ERROR,
            MessageType::INVOCATION->value,
            $invocationRequestId,
            (object) [],    // ← details → {}
            $error,
            $args,
        );
    }

    // -------------------------------------------------------------------------
    // PubSub - Publisher
    // -------------------------------------------------------------------------

    public static function publish(
        int $requestId,
        string $topic,
        array $args = [],
        array $kwargs = [],
        bool $acknowledge = false,
    ): WampMessage {
        $options = $acknowledge
            ? ['acknowledge' => true]
            : (object) [];

        return WampMessage::create(
            MessageType::PUBLISH,
            $requestId,
            $options,
            $topic,
            $args,
            (object) $kwargs ?: (object) [],
        );
    }

    // -------------------------------------------------------------------------
    // PubSub - Subscriber
    // -------------------------------------------------------------------------

    public static function subscribe(int $requestId, string $topic): WampMessage
    {
        return WampMessage::create(
            MessageType::SUBSCRIBE,
            $requestId,
            (object) [],    // options → {}
            $topic,
        );
    }

    public static function unsubscribe(int $requestId, int $subscriptionId): WampMessage
    {
        return WampMessage::create(
            MessageType::UNSUBSCRIBE,
            $requestId,
            $subscriptionId,
        );
    }
}
