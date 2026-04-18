<?php

namespace Hermod\Contracts;

interface CalleeContract
{
    /**
     * Registra una procedura sul router WAMP.
     *
     * @param  string  $procedure  URI della procedura es: com.myapp.somma
     * @param  callable  $handler  Funzione che gestisce le invocazioni
     */
    public function register(string $procedure, callable $handler): void;

    /**
     * Deregistra una procedura dal router WAMP.
     */
    public function unregister(string $procedure): void;

    /**
     * Restituisce tutte le procedure registrate.
     *
     * @return array<string, callable>
     */
    public function getRegistrations(): array;
}
