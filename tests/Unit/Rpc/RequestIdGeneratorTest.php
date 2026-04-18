<?php

use Hermod\Rpc\RequestIdGenerator;

describe('RequestIdGenerator', function () {

    beforeEach(function () {
        $this->generator = new RequestIdGenerator();
    });

    it('genera un intero positivo', function () {
        expect($this->generator->generate())
            ->toBeInt()
            ->toBeGreaterThan(0);
    });

    it('rispetta il range WAMP [1, 2^53]', function () {
        $id = $this->generator->generate();

        expect($id)
            ->toBeGreaterThanOrEqual(1)
            ->toBeLessThanOrEqual(9007199254740992);
    });

    it('genera ID diversi in chiamate successive', function () {
        $ids = array_map(
            fn() => $this->generator->generate(),
            range(1, 100)
        );

        // Con 100 ID casuali in un range di 2^53 la probabilità
        // di collisione è astronomicamente bassa
        expect(count(array_unique($ids)))->toBe(100);
    });
});
