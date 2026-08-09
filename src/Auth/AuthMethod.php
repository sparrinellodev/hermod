<?php

namespace Hermod\LaravelWamp\Auth;

/**
 * Defines the supported WAMP authentication methods.
 */
enum AuthMethod: string
{
    /**
     * Anonymous authentication.
     * No credentials are provided. Typically used for public endpoints.
     */
    case Anonymous = 'anonymous';

    /**
     * Ticket-based authentication.
     * Uses a single pre-shared secret (e.g., an API key or a static token).
     */
    case Ticket = 'ticket';

    /**
     * Challenge-Response Authentication (WAMP-CRA).
     * Provides secure authentication using HMAC signatures without transmitting the secret.
     */
    case WampCra = 'wampcra';
}