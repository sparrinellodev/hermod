# Hermod — WAMP Client for Laravel

[![Tests](https://github.com/hermod/laravel-wamp/actions/workflows/tests.yml/badge.svg)](https://github.com/hermod/laravel-wamp/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/hermod/laravel-wamp/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/hermod/laravel-wamp/actions/workflows/static-analysis.yml)
[![Latest Version](https://img.shields.io/packagist/v/hermod/laravel-wamp.svg)](https://packagist.org/packages/hermod/laravel-wamp)
[![PHP Version](https://img.shields.io/packagist/php-v/hermod/laravel-wamp.svg)](https://packagist.org/packages/hermod/laravel-wamp)
[![License](https://img.shields.io/packagist/l/hermod/laravel-wamp.svg)](LICENSE.md)

Hermod is a modern WAMP v2 client for Laravel 12+, built on AMPHP v3
and PHP Fibers. Born as an actively maintained alternative to Thruway,
it supports RPC (Caller/Callee) and PubSub with JSON serialization, MessagePack, and CBOR.

> **In Norse mythology, Hermod is the messenger of the gods —
> the one who carries messages between realms.**

---

## Requirements

- PHP 8.2+
- Laravel 12.x or 13.x
- A WAMP v2 router (e.g., [Crossbar.io](https://crossbar.io))

---

## Installation

```bash
composer require hermod/laravel-wamp
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=hermod-config
```

Add the variables to your `.env`:

```env
WAMP_URL=ws://localhost:8080/ws
WAMP_REALM=realm1
WAMP_SERIALIZER=json
```

---

## Fast use

### Caller — call a remote procedure

```php
use Hermod\Laravel\Facades\Wamp;

Wamp::connect();

// Chiamata sincrona
$result = Wamp::call('com.myapp.somma', [3, 5]);
// → 8

// Chiamata asincrona
$future = Wamp::callAsync('com.myapp.somma', [10, 20]);
$result = $future->await();
// → 30

Wamp::disconnect();
```

### Callee — record a procedure

In your `AppServiceProvider`:

```php
use Hermod\Laravel\Events\WampServeStarted;

Event::listen(WampServeStarted::class, function (WampServeStarted $event) {
$event->client->register('com.myapp.somma', function (array $args): int {
return $args[0] + $args[1];
});

$event->client->register('com.myapp.utente', function (array $args): array {
return User::find($args[0])?->toArray() ?? [];
});
});
```

Start the worker:

```bash
php artisan wamp:serve
```

### Terminal debugging

```bash
# Call a procedure directly from the terminal
php artisan wamp:call com.myapp.somma 3 5
php artisan wamp:call com.myapp.saluta --kwargs='{"nome":"Mario"}'
```

---

## Serializers

| Driver    | Subprotocol      | Stato      | Note                              |
| --------- | ---------------- | ---------- | --------------------------------- |
| `json`    | `wamp.2.json`    | ✅ Stable  | Default, no extra dependencies    |
| `cbor`    | `wamp.2.cbor`    | ✅ Stable  | Requires `spomky-labs/cbor-php`   |
| `msgpack` | `wamp.2.msgpack` | 🔜 v1.0    | Requires `ext-msgpack`            |

```env
WAMP_SERIALIZER=cbor
```

---

## Roadmap

| Version  | Content                                         |
| -------- | ----------------------------------------------- |
| **v0.1** | RPC Core — Caller, Callee, JSON, CBOR ✅        |
| **v0.2** | PubSub — Publisher, Subscriber                  |
| **v0.3** | Auth — WAMP-CRA, Ticket, Reconnect              |
| **v1.0** | MessagePack, RawSocket, Complete documentation  |

---

## Testing

```bash
./vendor/bin/pest
./vendor/bin/pest --coverage
```

---

## Contribute

Read [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## License

Hermod is open source software released under the [MIT] License (LICENSE.md).
