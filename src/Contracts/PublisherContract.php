<?php

namespace Hermod\Contracts;

use Amp\Future;

interface PublisherContract
{
    /**
     * Pubblica un evento su un topic senza attendere conferma.
     * Fire and forget — il router non risponde con PUBLISHED.
     *
     * @param  string  $topic  URI del topic es: com.myapp.notifiche
     * @param  array  $args  Argomenti posizionali
     * @param  array  $kwargs  Argomenti nominali
     */
    public function publish(string $topic, array $args = [], array $kwargs = []): void;

    /**
     * Pubblica un evento e attende la conferma PUBLISHED dal router.
     * Richiede che il router supporti l'opzione acknowledge.
     *
     * @return Future<int> Risolve con il publication ID assegnato dal router
     */
    public function publishWithAck(string $topic, array $args = [], array $kwargs = []): Future;
}
