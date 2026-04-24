# Changelog

All relevant changes to Hermod are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/it/1.0.0/),
and the project adopts [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned — v1.0.0

- RawSocket transport (TCP and Unix)
- Complete MessagePack serializer
- Complete documentation

## [0.3.0] - 2026-04-24

### Added

- Full Auth Layer:
- `AnonymousAuthenticator` — anonymous authentication (default)
- `TicketAuthenticator` — static or dynamic token authentication
- `WampCraAuthenticator` — WAMP-CRA authentication with HMAC-SHA256 and salted PBKDF2 support
- `AuthenticatorFactory` — resolves the authenticator from the configuration
- `AuthMethod` enum — strong typing of authentication methods
- New WAMP messages:
- `CHALLENGE` (type 4) in `MessageType`
- `AUTHENTICATE` (type 5) in `MessageType`
- `MessageFactory::helloWithAuth()` — HELLO with authmethods and authid support
- `MessageFactory::authenticate()` — AUTHENTICATE message with signature
- Resilience:
- `ExponentialBackoffStrategy` — Configurable exponential backoff with a cap on the maximum delay
- `ReconnectManager` — Automatic reconnect management with procedure and subscription reset
- New contracts:
- `AuthenticatorContract`
- `ReconnectStrategyContract`
- New exceptions:
- `AuthenticationException` — Authentication errors with wampError and details
- `ReconnectException` — Exhausted reconnect attempts
- `WampSession` updated with auth sequence management: HELLO → [CHALLENGE → AUTHENTICATE] → WELCOME
- `WampClientFactory` updated with `AuthenticatorFactory` and `ReconnectManager`
- `WampClient` updated with automatic reconnect in `listen()`
- `WampServiceProvider` updated with registration AuthenticatorFactory
- Updated `config/hermod.php` with `auth`, `reconnect`, and `heartbeat` sections
- Complete unit testing for Auth and Resilience

### Fixed

- `WampSession::hello()` now uses `helloWithAuth()` for all methods
  ensuring compatibility with routers that require authmethods in the HELLO

## [0.2.0] - 2026-04-20

### Added

- Complete PubSub Layer:
- `Publisher` with `publish()` (fire and forget) and `publishWithAck()` (with confirmation) support
- `Subscriber` with registration handler and `subscribe()`/`unsubscribe()` management
- `Subscription` — object representing an active subscription
- `PendingSubscriptionRegistry` — management of subscriptions awaiting confirmation
- New contracts:
- `PublisherContract`
- `SubscriberContract`
- Laravel `WampEventReceived` event — automatically dispatched with each WAMP EVENT received
- New methods in `MessageFactory`: `publish()`, `subscribe()`, `unsubscribe()`
- `MessageDispatcher` updated to handle `PUBLISHED`, `SUBSCRIBED`, `UNSUBSCRIBED`, `EVENT`
- `WampClient` and `WampClientFactory` updated with The PubSub Layer
- `WampClientContract` now extends `PublisherContract` and `SubscriberContract`
- `Wamp` facade updated with new PubSub methods
- Comprehensive unit testing for all PubSub components

### Fixed

- `MessageFactory` — the `options`, `details`, and `kwargs` fields now correctly serialize
  as `{}` instead of `[]` when empty (critical fix for WAMP compatibility)
- `WebSocketTransport::buildHandshake()` — the subprotocol is now correctly passed
  as the `Sec-WebSocket-Protocol` HTTP header
- `WampSession::goodbye()` — correctly handles the case where
  the transport is already closed before the call
- `WampClient::listen()` — correctly distinguishes between clean router
  closure (GOODBYE) and connection loss
- `WampClient::call()` and `callAsync()` — internally start the message reception loop
  to resolve the Future even outside of an active AMPHP event loop

## [0.1.0] - 2026-04-18

### Added

- Core implementation of the WAMP v2 protocol
- Transport Layer via WebSocket (amphp/websocket-client)
- Session Layer with HELLO/WELCOME/ABORT/GOODBYE management
- Full RPC Layer:
- Caller with synchronous and asynchronous calls (Future)
- Callee with registration and INVOCATION management
- PendingCallRegistry for request/response correlation
- MessageDispatcher for message dispatching
- Serializers:
- JSON (default, ready to use)
- CBOR (via spomky-labs/cbor-php)
- MessagePack (ready-made structure, requires ext-msgpack)
- Laravel 12/13 integration:
- WampServiceProvider with auto-discovery
- `Wamp` facade
- Artisan `wamp:serve` command
- Artisan `wamp:call` command
- Event `WampServeStarted`
- Complete unit testing with Pest
- Static analysis with PHPStan (level 6)
