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
            [],
            $reason,
        );
    }

    // -------------------------------------------------------------------------
    // RPC - Caller
    // -------------------------------------------------------------------------

    /**
     * Summary of call
     *
     * @param  array<mixed>  $args
     * @param  array<mixed>  $kwargs
     */
    public static function call(
        int $requestId,
        string $procedure,
        array $args = [],
        array $kwargs = [],
    ): WampMessage {
        return WampMessage::create(
            MessageType::CALL,
            $requestId,
            [],             // options
            $procedure,
            $args,
            $kwargs,
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
            [],             // options
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

    /**
     * Summary of yield
     *
     * @param  array<mixed>  $args
     * @param  array<mixed>  $kwargs
     */
    public static function yield(
        int $invocationRequestId,
        array $args = [],
        array $kwargs = [],
    ): WampMessage {
        return WampMessage::create(
            MessageType::YIELD,
            $invocationRequestId,
            [],             // options
            $args,
            $kwargs,
        );
    }

    /**
     * Summary of yieldError
     *
     * @param  array<mixed>  $args
     */
    public static function yieldError(
        int $invocationRequestId,
        string $error,
        array $args = [],
    ): WampMessage {
        return WampMessage::create(
            MessageType::ERROR,
            MessageType::INVOCATION->value,
            $invocationRequestId,
            [],             // details
            $error,
            $args,
        );
    }
}
