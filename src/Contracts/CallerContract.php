<?php

namespace Hermod\Contracts;

use Amp\Future;

interface CallerContract
{
    /**
     * Esegue una chiamata RPC bloccante e restituisce il risultato.
     *
     * @param  array<mixed>  $args  Argomenti posizionali
     * @param  array<mixed>  $kwargs  Argomenti nominali
     */
    public function call(string $procedure, array $args = [], array $kwargs = []): mixed;

    /**
     * Esegue una chiamata RPC asincrona e restituisce un Future.
     *
     * @param  array<mixed>  $args  Argomenti posizionali
     * @param  array<mixed>  $kwargs  Argomenti nominali
     * @return Future<mixed>
     */
    public function callAsync(string $procedure, array $args = [], array $kwargs = []): Future;
}
