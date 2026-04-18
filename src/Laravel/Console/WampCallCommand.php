<?php

namespace Hermod\Laravel\Console;

use Hermod\Client\WampClientFactory;
use Hermod\Exceptions\RpcException;
use Illuminate\Console\Command;

class WampCallCommand extends Command
{
    protected $signature = 'wamp:call
                            {procedure          : URI della procedura es: com.myapp.somma}
                            {args?*             : Argomenti posizionali}
                            {--connection=default : Nome della connessione in config/hermod.php}
                            {--kwargs=          : Argomenti nominali in formato JSON}
                            {--timeout=30       : Timeout in secondi}';

    protected $description = 'Esegui una chiamata RPC WAMP dal terminale';

    public function handle(WampClientFactory $factory): int
    {
        $procedure = $this->argument('procedure');
        $args      = $this->resolveArgs();
        $kwargs    = $this->resolveKwargs();
        $config    = $this->resolveConfig();

        $this->info("Chiamata RPC: {$procedure}");
        $this->line("Args:   " . json_encode($args));
        $this->line("Kwargs: " . json_encode($kwargs));
        $this->newLine();

        $client = $factory->make($config);

        try {
            $client->connect();
            $this->info("Connesso. Session ID: {$client->getSessionId()}");

            $start  = microtime(true);
            $result = $client->call($procedure, $args, $kwargs);
            $ms     = round((microtime(true) - $start) * 1000, 2);

            $this->newLine();
            $this->info("✓ Risultato ({$ms}ms):");
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        } catch (RpcException $e) {
            $this->newLine();
            $this->error("✗ Errore RPC: {$e->getMessage()}");
            $this->line("WAMP Error: {$e->wampError}");
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("✗ Errore: {$e->getMessage()}");
            return Command::FAILURE;
        } finally {
            if ($client->isConnected()) {
                $client->disconnect();
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveArgs(): array
    {
        $raw = $this->argument('args') ?? [];

        // Convertiamo i valori stringa in tipi PHP nativi dove possibile
        return array_map(function (string $value) {
            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float) $value : (int) $value;
            }

            return match (strtolower($value)) {
                'true'  => true,
                'false' => false,
                'null'  => null,
                default => $value,
            };
        }, $raw);
    }

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
            $this->warn("--kwargs non è JSON valido, verrà ignorato.");
            return [];
        }
    }

    private function resolveConfig(): array
    {
        $connectionName = $this->option('connection');
        $config         = config("hermod.connections.{$connectionName}", []);

        if (empty($config)) {
            $this->error("Connessione '{$connectionName}' non trovata in config/hermod.php");
            exit(Command::FAILURE);
        }

        return $config;
    }
}
