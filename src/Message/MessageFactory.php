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
        [$normalizedArgs, $normalizedKwargs] = self::normalizeArgs($args, $kwargs);

        return WampMessage::create(
            MessageType::CALL,
            $requestId,
            (object) [],
            $procedure,
            $normalizedArgs,
            $normalizedKwargs,
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
            (object) [],
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
        [$normalizedArgs, $normalizedKwargs] = self::normalizeArgs($args, $kwargs);

        return WampMessage::create(
            MessageType::YIELD,
            $invocationRequestId,
            (object) [],
            $normalizedArgs,
            $normalizedKwargs,
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
            (object) [],
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
        [$normalizedArgs, $normalizedKwargs] = self::normalizeArgs($args, $kwargs);

        $options = $acknowledge
            ? (object) ['acknowledge' => true]
            : (object) [];

        return WampMessage::create(
            MessageType::PUBLISH,
            $requestId,
            $options,
            $topic,
            $normalizedArgs,
            $normalizedKwargs,
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
            (object) [],
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

    private static function normalizeArgs(array $args, array $kwargs): array
    {
        if (! empty($args) && ! array_is_list($args)) {
            $kwargs = array_merge($kwargs, $args);
            $args = [];
        }

        // kwargs come stdClass per garantire {} anche se vuoto
        $normalizedKwargs = empty($kwargs)
            ? (object) []
            : (object) $kwargs;

        return [$args, $normalizedKwargs];
    }
}
