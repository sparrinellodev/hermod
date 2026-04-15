<?php

namespace Hermod\Contracts;

use Amp\Future;

interface CallerContract
{
    /**
     * Esegue una chiamata RPC bloccante e restituisce il risultato.
     *
     * @param string $procedure URI della procedura es: com.myapp.somma
     * @param array  $args      Argomenti posizionali
     * @param array  $kwargs    Argomenti nominali
     */
    public function call(string $procedure, array $args = [], array $kwargs = []): mixed;

    /**
     * Esegue una chiamata RPC asincrona e restituisce un Future.
     */
    public function callAsync(string $procedure, array $args = [], array $kwargs = []): Future;
}
