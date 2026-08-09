<?php

namespace Hermod\LaravelWamp\Laravel\Console;

use Hermod\LaravelWamp\Client\WampClient;
use Hermod\LaravelWamp\Client\WampClientFactory;
use Hermod\LaravelWamp\Exceptions\WampClientException;
use Hermod\LaravelWamp\Laravel\Events\WampServeStarted;
use Illuminate\Console\Command;

/**
 * Artisan command to start a WAMP Callee worker listening for RPC invocations.
 *
 * Handles configuration resolution (with CLI overrides), connecting to the router,
 * dispatching registration events for procedures, and managing the worker listener lifecycle.
 */
class WampServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wamp:serve
                            {--connection=default : Name of the connection in config/wamp.php}
                            {--url=              : WAMP router URL (overrides config)}
                            {--realm=            : WAMP realm (overrides config)}
                            {--serializer=       : Serializer to use: json|msgpack|cbor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a WAMP Callee worker listening for incoming RPC invocations';

    /**
     * Execute the console command.
     *
     * @param  \Hermod\LaravelWamp\Client\WampClientFactory  $factory  The WAMP client factory instance.
     * @return int Command exit code (Command::SUCCESS or Command::FAILURE).
     */
    public function handle(WampClientFactory $factory): int
    {
        $config = $this->resolveConfig();

        $this->info('Hermod WAMP Worker');
        $this->info('──────────────────────────────────────');
        $this->info("URL:         {$config['url']}");
        $this->info("Realm:       {$config['realm']}");
        $this->info("Serializer:  {$config['serializer']}");
        $this->info('──────────────────────────────────────');

        $client = $factory->make($config);

        try {
            $this->info('Connecting to WAMP router...');
            $client->connect();

            $this->info("Session established. Session ID: {$client->getSessionId()}");
            $this->info('Worker listening. Press Ctrl+C to exit.');
            $this->newLine();

            $this->registerProcedures($client);

            $client->listen();

            return Command::SUCCESS;
        } catch (WampClientException $e) {
            $this->error("WAMP Error: {$e->getMessage()}");

            if ($e->getPrevious()) {
                $this->line("<fg=gray>Caused by: {$e->getPrevious()->getMessage()}</>");
            }

            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Unexpected error: {$e->getMessage()}");

            return Command::FAILURE;
        } finally {
            // Always attempt a clean shutdown.
            // WampClient::disconnect() is robust and does not throw exceptions.
            try {
                if ($client->isConnected()) {
                    $client->disconnect();
                    $this->info('Disconnected from WAMP router.');
                }
            } catch (\Throwable) {
                // Ignore during shutdown
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve and merge configuration settings with CLI options.
     *
     * @return array<string, mixed> The final configuration array.
     */
    private function resolveConfig(): array
    {
        $connectionName = $this->option('connection');
        $config = config("wamp.connections.{$connectionName}", []);

        if (empty($config)) {
            $this->error("Connection '{$connectionName}' not found in config/wamp.php");
            exit(Command::FAILURE);
        }

        // CLI options override configuration settings
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

    /**
     * Register application RPC procedures via a dedicated event dispatch.
     *
     * @param  \Hermod\LaravelWamp\Client\WampClient  $client  The connected WAMP client instance.
     */
    private function registerProcedures(WampClient $client): void
    {
        // Allows the Laravel application to register its procedures 
        // via a dedicated event. Example in AppServiceProvider:
        //
        // Event::listen(WampServeStarted::class, function($event) {
        //     $event->client->register('com.myapp.sum', fn($args) => $args[0] + $args[1]);
        // });

        event(new WampServeStarted($client));
    }
}