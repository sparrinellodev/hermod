<?php

namespace Hermod\Laravel;

use Hermod\Auth\AuthenticatorFactory;
use Hermod\Client\WampClient;
use Hermod\Client\WampClientFactory;
use Hermod\Laravel\Console\WampCallCommand;
use Hermod\Laravel\Console\WampServeCommand;
use Hermod\Serializer\SerializerFactory;
use Hermod\Session\WampSessionFactory;
use Hermod\Transport\RawSocketTransportFactory;
use Hermod\Transport\TransportFactory;
use Hermod\Transport\WebSocketTransportFactory;
use Illuminate\Support\ServiceProvider;

class WampServiceProvider extends ServiceProvider
{
    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    public function boot(): void
    {
        $this->publishConfig();
    }

    // -------------------------------------------------------------------------
    // Register
    // -------------------------------------------------------------------------

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/hermod.php',
            'hermod',
        );

        $this->registerFactories();
        $this->registerClient();
        $this->registerCommands();
    }

    // -------------------------------------------------------------------------
    // Pubblicazione config
    // -------------------------------------------------------------------------

    private function publishConfig(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/hermod.php' => config_path('hermod.php'),
            ], 'hermod-config');
        }
    }

    // -------------------------------------------------------------------------
    // Factories
    // -------------------------------------------------------------------------

    private function registerFactories(): void
    {
        $this->app->singleton(SerializerFactory::class, function ($app) {
            return new SerializerFactory(
                $app['config']->get('hermod.serializers', []),
            );
        });

        $this->app->singleton(WebSocketTransportFactory::class, function () {
            return new WebSocketTransportFactory;
        });

        $this->app->singleton(RawSocketTransportFactory::class, function () {
            return new RawSocketTransportFactory;
        });

        $this->app->singleton(TransportFactory::class, function ($app) {
            return new TransportFactory(
                websocketFactory: $app->make(WebSocketTransportFactory::class),
                rawSocketFactory: $app->make(RawSocketTransportFactory::class),
            );
        });

        $this->app->singleton(WampSessionFactory::class, function () {
            return new WampSessionFactory;
        });

        $this->app->singleton(AuthenticatorFactory::class, function () {
            return new AuthenticatorFactory;
        });

        $this->app->singleton(WampClientFactory::class, function ($app) {
            return new WampClientFactory(
                serializerFactory: $app->make(SerializerFactory::class),
                transportFactory: $app->make(TransportFactory::class),
                sessionFactory: $app->make(WampSessionFactory::class),
                authenticatorFactory: $app->make(AuthenticatorFactory::class),
            );
        });
    }

    // -------------------------------------------------------------------------
    // Client
    // -------------------------------------------------------------------------

    private function registerClient(): void
    {
        // Binding principale — usa la connessione 'default'
        $this->app->singleton(WampClient::class, function ($app) {
            $connectionName = $app['config']->get('hermod.default', 'default');
            $config = $app['config']->get("hermod.connections.{$connectionName}");

            if (empty($config)) {
                throw new \RuntimeException(
                    "Configurazione Hermod non trovata per la connessione '{$connectionName}'.",
                );
            }

            return $app->make(WampClientFactory::class)->make($config);
        });

        // Alias stringa per la Facade
        $this->app->alias(WampClient::class, 'hermod.client');
    }

    // -------------------------------------------------------------------------
    // Comandi Artisan
    // -------------------------------------------------------------------------

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                WampServeCommand::class,
                WampCallCommand::class,
            ]);
        }
    }
}
