<?php

use Hermod\Laravel\Events\WampEventReceived;

describe('WampEventReceived', function () {

    it('memorizza correttamente tutti i campi dell\'evento', function () {
        $event = new WampEventReceived(
            topic: 'com.myapp.notifiche',
            subscriptionId: 7777,
            publicationId: 1234,
            args: ['ciao', 'mondo'],
            kwargs: ['chiave' => 'valore'],
            details: ['publisher' => 999],
        );

        expect($event->topic)->toBe('com.myapp.notifiche')
            ->and($event->subscriptionId)->toBe(7777)
            ->and($event->publicationId)->toBe(1234)
            ->and($event->args)->toBe(['ciao', 'mondo'])
            ->and($event->kwargs)->toBe(['chiave' => 'valore'])
            ->and($event->details)->toBe(['publisher' => 999]);
    });

    it('accetta arrays vuoti per args, kwargs e details', function () {
        $event = new WampEventReceived(
            topic: 'com.myapp.test',
            subscriptionId: 1,
            publicationId: 2,
            args: [],
            kwargs: [],
            details: [],
        );

        expect($event->args)->toBe([])
            ->and($event->kwargs)->toBe([])
            ->and($event->details)->toBe([]);
    });
});
