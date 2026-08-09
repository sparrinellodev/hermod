<?php

namespace Hermod\LaravelWamp\Auth;

use Hermod\LaravelWamp\Contracts\AuthenticatorContract;
use Hermod\LaravelWamp\Exceptions\AuthenticationException;

/**
 * Factory class to resolve and instantiate the appropriate WAMP authenticator.
 */
class AuthenticatorFactory
{
    /**
     * Create an authenticator instance based on the provided configuration.
     *
     * @param  array<string, mixed>  $config  The authentication configuration array.
     * @return \Hermod\LaravelWamp\Contracts\AuthenticatorContract
     *
     * @throws \Hermod\LaravelWamp\Exceptions\AuthenticationException If the method is unsupported or missing required parameters.
     */
    public function make(array $config): AuthenticatorContract
    {
        $method = AuthMethod::tryFrom($config['method'] ?? 'anonymous');

        if ($method === null) {
            throw new AuthenticationException(
                "Unsupported authentication method: '{$config['method']}'. " .
                "Accepted values: anonymous, ticket, wampcra."
            );
        }

        return match ($method) {
            AuthMethod::Anonymous => new AnonymousAuthenticator,

            AuthMethod::Ticket => new TicketAuthenticator(
                authId: $config['authid'] ?? throw new AuthenticationException(
                    "Ticket authentication requires an 'authid' to be present in the configuration."
                ),
                ticket: $config['ticket'] ?? throw new AuthenticationException(
                    "Ticket authentication requires a 'ticket' to be present in the configuration."
                ),
            ),

            AuthMethod::WampCra => new WampCraAuthenticator(
                authId: $config['authid'] ?? throw new AuthenticationException(
                    "WAMP-CRA authentication requires an 'authid' to be present in the configuration."
                ),
                secret: $config['secret'] ?? throw new AuthenticationException(
                    "WAMP-CRA authentication requires a 'secret' to be present in the configuration."
                ),
            ),
        };
    }
}