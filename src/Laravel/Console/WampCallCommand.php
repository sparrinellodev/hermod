<?php

namespace Hermod\LaravelWamp\Laravel\Console;

use function Amp\async;

use Hermod\LaravelWamp\Client\WampClientFactory;
use Hermod\LaravelWamp\Exceptions\RpcException;
use Illuminate\Console\Command;

/**
 * Artisan command to execute WAMP Remote Procedure Calls (RPC) from the terminal.
 *
 * Handles argument parsing, option resolution (such as JSON kwargs and connection config),
 * asynchronous execution via AMPHP, and formatted console output.
 */
class WampCallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wamp:call
                            {procedure            : The procedure URI, e.g., com.myapp.sum}
                            {args?* : Positional arguments}
                            {--connection=default : Name of the connection in config/wamp.php}
                            {--kwargs=            : Keyword arguments in JSON format}
                            {--timeout=30         : Timeout in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute a WAMP RPC call from the terminal';

    /**
     * Execute the console command.
     *
     * @param  \Hermod\LaravelWamp\Client\WampClientFactory  $factory  The WAMP client factory instance.
     * @return int Command exit code (Command::SUCCESS or Command::FAILURE).
     */
    public function handle(WampClientFactory $factory): int
    {
        $procedure = $this->argument('procedure');
        $args = $this->resolveArgs();
        $kwargs = $this->resolveKwargs();
        $config = $this->resolveConfig();

        $this->info("RPC Call: {$procedure}");
        $this->line('Args:   ' . json_encode($args));
        $this->line('Kwargs: ' . json_encode($kwargs));
        $this->newLine();

        $client = $factory->make($config);

        // Execute everything within the AMPHP event loop
        return async(function () use ($client, $procedure, $args, $kwargs): int {
            try {
                $client->connect();
                $this->info("Connected. Session ID: {$client->getSessionId()}");

                $start = microtime(true);
                $result = $client->call($procedure, $args, $kwargs);
                $ms = round((microtime(true) - $start) * 1000, 2);

                $this->newLine();
                $this->info("✓ Result ({$ms}ms):");
                $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                return Command::SUCCESS;
            } catch (RpcException $e) {
                $this->newLine();
                $this->error("✗ RPC Error: {$e->getMessage()}");
                $this->line("WAMP Error: {$e->wampError}");

                return Command::FAILURE;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("✗ Error: {$e->getMessage()}");

                return Command::FAILURE;
            } finally {
                try {
                    if ($client->isConnected()) {
                        $client->disconnect();
                    }
                } catch (\Throwable) {
                    // Suppress cleanup exceptions
                }
            }
        })->await();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Parse and cast positional arguments passed via the command line.
     *
     * @return array<mixed> The processed arguments.
     */
    private function resolveArgs(): array
    {
        $raw = $this->argument('args') ?? [];

        return array_map(function (string $value) {
            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float) $value : (int) $value;
            }

            return match (strtolower($value)) {
                'true' => true,
                'false' => false,
                'null' => null,
                default => $value,
            };
        }, $raw);
    }

    /**
     * Parse keyword arguments provided as a JSON string option.
     *
     * @return array<string, mixed> The decoded keyword arguments, or empty array on failure.
     */
    private function resolveKwargs(): array
    {
        $raw = $this->option('kwargs');

        if (empty($raw)) {
            return [];
        }

        try {
            $decoded = json_decode($raw, associative: true, flags: JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            $this->warn('--kwargs is not valid JSON and will be ignored.');

            return [];
        }
    }

    /**
     * Resolve the connection configuration array based on the command option.
     *
     * @return array<string, mixed> The connection configuration settings.
     */
    private function resolveConfig(): array
    {
        $connectionName = $this->option('connection');
        $config = config("wamp.connections.{$connectionName}", []);

        if (empty($config)) {
            $this->error("Connection '{$connectionName}' not found in config/wamp.php");
            exit(Command::FAILURE);
        }

        return $config;
    }
}