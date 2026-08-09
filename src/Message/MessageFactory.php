<?php

namespace Hermod\LaravelWamp\Message;

/**
 * Factory class for constructing standardized WAMP protocol messages.
 *
 * This class provides static helper methods to easily generate properly formatted
 * WampMessage instances for all supported WAMP roles (Caller, Callee, Publisher, Subscriber)
 * and session management actions.
 */
class MessageFactory
{
    // -------------------------------------------------------------------------
    // Session
    // -------------------------------------------------------------------------

    /**
     * Create a HELLO message to initiate a WAMP session.
     *
     * @param  string  $realm  The WAMP realm to join.
     * @return WampMessage
     */
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

    /**
     * Create a GOODBYE message to gracefully close a WAMP session.
     *
     * @param  string  $reason  The WAMP URI indicating the closure reason.
     * @return WampMessage
     */
    public static function goodbye(string $reason = 'wamp.close.normal'): WampMessage
    {
        return WampMessage::create(
            MessageType::GOODBYE,
            (object) [],    // ← Empty object ensures {} instead of [] in JSON
            $reason,
        );
    }

    // -------------------------------------------------------------------------
    // RPC - Caller
    // -------------------------------------------------------------------------

    /**
     * Create a CALL message to invoke a remote procedure.
     *
     * @param  int  $requestId  The unique request ID for this call.
     * @param  string  $procedure  The URI of the procedure to invoke.
     * @param  array<mixed>  $args  Positional arguments.
     * @param  array<string, mixed>  $kwargs  Keyword arguments.
     * @return WampMessage
     */
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

    /**
     * Create a REGISTER message to expose a local procedure to the WAMP router.
     *
     * @param  int  $requestId  The unique request ID for this registration attempt.
     * @param  string  $procedure  The URI of the procedure being registered.
     * @return WampMessage
     */
    public static function register(int $requestId, string $procedure): WampMessage
    {
        return WampMessage::create(
            MessageType::REGISTER,
            $requestId,
            (object) [],
            $procedure,
        );
    }

    /**
     * Create an UNREGISTER message to remove a previously registered procedure.
     *
     * @param  int  $requestId  The unique request ID for this unregistration attempt.
     * @param  int  $registrationId  The ID of the registration to remove.
     * @return WampMessage
     */
    public static function unregister(int $requestId, int $registrationId): WampMessage
    {
        return WampMessage::create(
            MessageType::UNREGISTER,
            $requestId,
            $registrationId,
        );
    }

    /**
     * Create a YIELD message to return a successful result for an RPC invocation.
     *
     * @param  int  $invocationRequestId  The request ID matching the incoming INVOCATION message.
     * @param  array<mixed>  $args  Positional return values.
     * @param  array<string, mixed>  $kwargs  Keyword return values.
     * @return WampMessage
     */
    public static function yield(
        int $invocationRequestId,
        array $args = [],
        array $kwargs = [],
    ): WampMessage {
        [$normalizedArgs, $normalizedKwargs] = self::normalizeArgs($args, $kwargs);

        return WampMessage::create(
            MessageType::YIELD ,
            $invocationRequestId,
            (object) [],
            $normalizedArgs,
            $normalizedKwargs,
        );
    }

    /**
     * Create an ERROR message to report a failure during an RPC invocation.
     *
     * @param  int  $invocationRequestId  The request ID matching the incoming INVOCATION message.
     * @param  string  $error  The WAMP URI indicating the error type.
     * @param  array<mixed>  $args  Additional positional arguments containing error details.
     * @return WampMessage
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
            (object) [],
            $error,
            $args,
        );
    }

    // -------------------------------------------------------------------------
    // PubSub - Publisher
    // -------------------------------------------------------------------------

    /**
     * Create a PUBLISH message to broadcast an event to a topic.
     *
     * @param  int  $requestId  The unique request ID for this publication.
     * @param  string  $topic  The URI of the topic.
     * @param  array<mixed>  $args  Positional arguments for the event payload.
     * @param  array<string, mixed>  $kwargs  Keyword arguments for the event payload.
     * @param  bool  $acknowledge  Whether to request an acknowledgment from the router.
     * @return WampMessage
     */
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

    /**
     * Create a SUBSCRIBE message to listen for events on a specific topic.
     *
     * @param  int  $requestId  The unique request ID for this subscription attempt.
     * @param  string  $topic  The URI of the topic to subscribe to.
     * @return WampMessage
     */
    public static function subscribe(int $requestId, string $topic): WampMessage
    {
        return WampMessage::create(
            MessageType::SUBSCRIBE,
            $requestId,
            (object) [],
            $topic,
        );
    }

    /**
     * Create an UNSUBSCRIBE message to stop listening to a topic.
     *
     * @param  int  $requestId  The unique request ID for this unsubscription attempt.
     * @param  int  $subscriptionId  The ID of the active subscription to remove.
     * @return WampMessage
     */
    public static function unsubscribe(int $requestId, int $subscriptionId): WampMessage
    {
        return WampMessage::create(
            MessageType::UNSUBSCRIBE,
            $requestId,
            $subscriptionId,
        );
    }

    /**
     * Normalize arguments to ensure WAMP protocol compliance.
     *
     * If an associative array is accidentally passed as `$args`, it gets merged into `$kwargs`.
     * Ensures that `$kwargs` is cast to an object so that JSON serializers encode it
     * as an empty dictionary `{}` rather than an empty list `[]`.
     *
     * @param  array<mixed>  $args
     * @param  array<mixed>  $kwargs
     * @return array{0: array<mixed>, 1: object} Tuple containing normalized args and kwargs.
     */
    private static function normalizeArgs(array $args, array $kwargs): array
    {
        if (!empty($args) && !array_is_list($args)) {
            $kwargs = array_merge($kwargs, $args);
            $args = [];
        }

        // kwargs as stdClass to guarantee {} even if empty
        $normalizedKwargs = empty($kwargs)
            ? (object) []
            : (object) $kwargs;

        return [$args, $normalizedKwargs];
    }

    // -------------------------------------------------------------------------
    // Auth
    // -------------------------------------------------------------------------

    /**
     * Create an advanced HELLO message configured for authentication.
     *
     * @param  string  $realm  The WAMP realm to join.
     * @param  string  $authMethod  The authentication method (e.g., 'ticket', 'wampcra').
     * @param  string|null  $authId  The authentication identifier (username/client ID). Optional.
     * @param  array<string, mixed>  $authExtra  Additional authentication parameters.
     * @return WampMessage
     */
    public static function helloWithAuth(
        string $realm,
        string $authMethod,
        ?string $authId = null,
        array $authExtra = [],
    ): WampMessage {
        $details = [
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
            'authmethods' => [$authMethod],
            'authextra' => empty($authExtra) ? (object) [] : $authExtra,
        ];

        // authid is optional — anonymous connections typically don't send it
        if ($authId !== null) {
            $details['authid'] = $authId;
        }

        return WampMessage::create(
            MessageType::HELLO,
            $realm,
            $details,
        );
    }

    /**
     * Create an AUTHENTICATE message to respond to a router challenge.
     *
     * @param  string  $signature  The computed signature or ticket to present to the router.
     * @param  array<string, mixed>  $extra  Additional payload data to accompany the signature.
     * @return WampMessage
     */
    public static function authenticate(string $signature, array $extra = []): WampMessage
    {
        return WampMessage::create(
            MessageType::AUTHENTICATE,
            $signature,
            empty($extra) ? (object) [] : $extra,
        );
    }
}