# Changelog

All relevant changes to Hermod are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/it/1.0.0/),
and the project adopts [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
