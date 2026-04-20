<?php

namespace Hermod\Contracts;

use Hermod\PubSub\Subscription;

interface SubscriberContract
{
    /**
     * Sottoscrive un topic e registra un handler per gli eventi ricevuti.
     *
     * @param  string  $topic  URI del topic es: com.myapp.notifiche
     * @param  callable  $handler  Funzione chiamata ad ogni EVENT ricevuto
     *                             Firma: function(array $args, array $kwargs, array $details): void
     * @return Subscription Oggetto che rappresenta la sottoscrizione attiva
     */
    public function subscribe(string $topic, callable $handler): Subscription;

    /**
     * Cancella la sottoscrizione a un topic tramite URI.
     */
    public function unsubscribe(string $topic): void;

    /**
     * Cancella la sottoscrizione tramite oggetto Subscription.
     */
    public function unsubscribeById(Subscription $subscription): void;

    /**
     * Restituisce tutte le sottoscrizioni attive.
     *
     * @return array<string, Subscription>
     */
    public function getSubscriptions(): array;
}
