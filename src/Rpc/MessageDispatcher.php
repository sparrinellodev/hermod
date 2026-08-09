<?php

namespace Hermod\LaravelWamp\Rpc;

use Hermod\LaravelWamp\Exceptions\WampProtocolException;
use Hermod\LaravelWamp\Message\MessageType;
use Hermod\LaravelWamp\Message\WampMessage;
use Hermod\LaravelWamp\PubSub\Publisher;
use Hermod\LaravelWamp\PubSub\Subscriber;
use Hermod\LaravelWamp\Session\WampSession;

/**
 * Dispatches incoming WAMP protocol messages to their respective domain handlers.
 *
 * Evaluates message types coming across the session and routes them to the Caller, 
 * Callee, Publisher, or Subscriber components, while managing cross-cutting error 
 * routing and unexpected session closure (GOODBYE) protocols.
 */
class MessageDispatcher
{
    /**
     * Create a new MessageDispatcher instance.
     *
     * @param  \Hermod\LaravelWamp\Session\WampSession  $session  The active WAMP session handler.
     * @param  \Hermod\LaravelWamp\Rpc\Caller  $caller  The RPC caller handler.
     * @param  \Hermod\LaravelWamp\Rpc\Callee  $callee  The RPC callee handler.
     * @param  \Hermod\LaravelWamp\PubSub\Publisher  $publisher  The Pub/Sub publisher handler.
     * @param  \Hermod\LaravelWamp\PubSub\Subscriber  $subscriber  The Pub/Sub subscriber handler.
     */
    public function __construct(
        private readonly WampSession $session,
        private readonly Caller $caller,
        private readonly Callee $callee,
        private readonly Publisher $publisher,
        private readonly Subscriber $subscriber,
    ) {
    }

    /**
     * Dispatch an incoming WAMP message to the appropriate functional handler.
     *
     * @param  \Hermod\LaravelWamp\Message\WampMessage  $message  The incoming protocol message.
     */
    public function dispatch(WampMessage $message): void
    {
        match ($message->type()) {
                // RPC - Caller
            MessageType::RESULT => $this->caller->onResult($message),
            MessageType::ERROR => $this->dispatchError($message),

                // RPC - Callee
            MessageType::REGISTERED => $this->callee->onRegistered($message),
            MessageType::UNREGISTERED => $this->callee->onUnregistered($message),
            MessageType::INVOCATION => $this->callee->onInvocation($message),

                // PubSub - Publisher
            MessageType::PUBLISHED => $this->publisher->onPublished($message),

                // PubSub - Subscriber
            MessageType::SUBSCRIBED => $this->subscriber->onSubscribed($message),
            MessageType::UNSUBSCRIBED => $this->subscriber->onUnsubscribed($message),
            MessageType::EVENT => $this->subscriber->onEvent($message),

                // Handle unexpected GOODBYE messages initiated by the router
            MessageType::GOODBYE => $this->handleUnexpectedGoodbye($message),

            // Unhandled messages are silently ignored
            default => null,
        };
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Route cross-cutting ERROR messages based on the original request type.
     * Expected format: [8, requestType, requestId, ...]
     *
     * @param  \Hermod\LaravelWamp\Message\WampMessage  $message  The incoming ERROR message.
     */
    private function dispatchError(WampMessage $message): void
    {
        $requestType = MessageType::tryFrom((int) $message->get(1));

        match ($requestType) {
            MessageType::CALL => $this->caller->onError($message),
            MessageType::REGISTER => null,
            MessageType::PUBLISH => $this->publisher->onError($message),
            MessageType::SUBSCRIBE => null,
            default => null,
        };
    }

    /**
     * Handle unexpected router-initiated session termination (GOODBYE).
     *
     * @param  \Hermod\LaravelWamp\Message\WampMessage  $message  The incoming GOODBYE message.
     *
     * @throws \Hermod\LaravelWamp\Exceptions\WampProtocolException Always thrown to signal unexpected closure.
     */
    private function handleUnexpectedGoodbye(WampMessage $message): void
    {
        $reason = (string) ($message->get(2) ?? 'wamp.close.unknown');

        throw new WampProtocolException(
            "The router closed the session unexpectedly: {$reason}",
            wampError: $reason,
        );
    }
}