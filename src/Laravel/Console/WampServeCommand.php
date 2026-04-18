<?php

namespace Hermod\Laravel\Console;

use Hermod\Client\WampClient;
use Hermod\Client\WampClientFactory;
use Hermod\Exceptions\WampClientException;
use Illuminate\Console\Command;

class WampServeCommand extends Command
{
    protected $signature = 'wamp:serve
                            {--connection=default : Nome della connessione in config/hermod.php}
                            {--url=              : URL del router WAMP (sovrascrive la config)}
                            {--realm=            : Realm WAMP (sovrascrive la config)}
                            {--serializer=       : Serializzatore da usare: json|msgpack|cbor}';

    protected $description = 'Avvia un worker Callee WAMP in ascolto di invocazioni RPC';

    public function handle(WampClientFactory $factory): int
    {
        $config = $this->resolveConfig();

        $this->info("Hermod WAMP Worker");
        $this->info("──────────────────────────────────────");
        $this->info("URL:         {$config['url']}");
        $this->info("Realm:       {$config['realm']}");
        $this->info("Serializer:  {$config['serializer']}");
        $this->info("──────────────────────────────────────");

        $client = $factory->make($config);

        try {
            $this->info("Connessione al router WAMP...");
            $client->connect();

            $this->info("Sessione stabilita. Session ID: {$client->getSessionId()}");
            $this->info("Worker in ascolto. Premi Ctrl+C per uscire.");
            $this->newLine();

            // Registra le procedure definite nell'app tramite evento
            $this->registerProcedures($client);

            // Avvia il loop di ascolto
            $client->listen();
        } catch (WampClientException $e) {
            $this->error("Errore WAMP: {$e->getMessage()}");
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Errore inatteso: {$e->getMessage()}");
            return Command::FAILURE;
        } finally {
            if ($client->isConnected()) {
                $client->disconnect();
                $this->info("Disconnesso dal router WAMP.");
            }
        }

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveConfig(): array
    {
        $connectionName = $this->option('connection');
        $config         = config("hermod.connections.{$connectionName}", []);

        if (empty($config)) {
            $this->error("Connessione '{$connectionName}' non trovata in config/hermod.php");
            exit(Command::FAILURE);
        }

        // Le opzioni CLI sovrascrivono la config
        if ($url = $this->option('url')) {
            $config['url'] = $url;
        }

        if ($realm = $this->option('realm')) {
            $config['realm'] = $realm;
        }

        if ($serializer = $this->option('serializer')) {
            $config['serializer'] = $serializer;
        }

        return $config;
    }

    private function registerProcedures(WampClient $client): void
    {
        // Permette all'applicazione Laravel di registrare
        // le proprie procedure tramite un evento dedicato.
        // Esempio in AppServiceProvider:
        //
        // Event::listen(WampServeStarted::class, function($event) {
        //     $event->client->register('com.myapp.somma', fn($args) => $args[0] + $args[1]);
        // });

        event(new \Hermod\Laravel\Events\WampServeStarted($client));
    }
}
