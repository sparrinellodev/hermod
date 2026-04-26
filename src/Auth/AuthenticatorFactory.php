<?php

namespace Hermod\Auth;

use Hermod\Contracts\AuthenticatorContract;
use Hermod\Exceptions\AuthenticationException;

class AuthenticatorFactory
{
    /**
     * Summary of make
     *
     * @param  array<string, mixed>  $config
     * @return AnonymousAuthenticator|TicketAuthenticator|WampCraAuthenticator
     */
    public function make(array $config): AuthenticatorContract
    {
        $method = AuthMethod::tryFrom($config['method'] ?? 'anonymous');

        if ($method === null) {
            throw new AuthenticationException(
                "Metodo di autenticazione non supportato: '{$config['method']}'. ".
                    'Valori accettati: anonymous, ticket, wampcra',
            );
        }

        return match ($method) {
            AuthMethod::Anonymous => new AnonymousAuthenticator,

            AuthMethod::Ticket => new TicketAuthenticator(
                authId: $config['authid'] ?? throw new AuthenticationException(
                    "Ticket auth richiede 'authid' nella configurazione.",
                ),
                ticket: $config['ticket'] ?? throw new AuthenticationException(
                    "Ticket auth richiede 'ticket' nella configurazione.",
                ),
            ),

            AuthMethod::WampCra => new WampCraAuthenticator(
                authId: $config['authid'] ?? throw new AuthenticationException(
                    "WAMP-CRA richiede 'authid' nella configurazione.",
                ),
                secret: $config['secret'] ?? throw new AuthenticationException(
                    "WAMP-CRA richiede 'secret' nella configurazione.",
                ),
            ),
        };
    }
}
