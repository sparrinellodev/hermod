<?php

use Hermod\LaravelWamp\Reconnect\ExponentialBackoffStrategy;

describe('ExponentialBackoffStrategy', function () {

    beforeEach(function () {
        $this->strategy = new ExponentialBackoffStrategy(
            maxAttempts: 5,
            baseDelay: 1.0,
            maxDelay: 30.0,
            multiplier: 2.0,
        );
    });

    it('parte con zero tentativi', function () {
        expect($this->strategy->attempts())->toBe(0);
    });

    it('permette retry finché i tentativi non si esauriscono', function () {
        expect($this->strategy->shouldRetry())->toBeTrue();

        for ($i = 0; $i < 5; $i++) {
            $this->strategy->recordFailure();
        }

        expect($this->strategy->shouldRetry())->toBeFalse();
    });

    it('inizia con il base delay', function () {
        expect($this->strategy->nextDelay())->toBe(1.0);
    });

    it('applica il backoff esponenziale correttamente', function () {
        expect($this->strategy->nextDelay())->toBe(1.0);  // prima del primo fallimento

        $this->strategy->recordFailure();
        expect($this->strategy->nextDelay())->toBe(2.0);  // 1.0 * 2

        $this->strategy->recordFailure();
        expect($this->strategy->nextDelay())->toBe(4.0);  // 2.0 * 2

        $this->strategy->recordFailure();
        expect($this->strategy->nextDelay())->toBe(8.0);  // 4.0 * 2

        $this->strategy->recordFailure();
        expect($this->strategy->nextDelay())->toBe(16.0); // 8.0 * 2
    });

    it('non supera il max delay', function () {
        // Con maxDelay=30 e multiplier=2 partendo da 1
        // dopo 5 recordFailure il delay sarebbe 32 → cappato a 30
        for ($i = 0; $i < 5; $i++) {
            $this->strategy->recordFailure();
        }

        expect($this->strategy->nextDelay())->toBe(30.0);
    });

    it('incrementa il contatore dei tentativi', function () {
        $this->strategy->recordFailure();
        expect($this->strategy->attempts())->toBe(1);

        $this->strategy->recordFailure();
        expect($this->strategy->attempts())->toBe(2);

        $this->strategy->recordFailure();
        expect($this->strategy->attempts())->toBe(3);
    });

    it('resetta correttamente dopo reset()', function () {
        $this->strategy->recordFailure();
        $this->strategy->recordFailure();
        $this->strategy->recordFailure();

        $this->strategy->reset();

        expect($this->strategy->attempts())->toBe(0)
            ->and($this->strategy->nextDelay())->toBe(1.0)
            ->and($this->strategy->shouldRetry())->toBeTrue();
    });

    it('funziona con maxAttempts=1', function () {
        $strategy = new ExponentialBackoffStrategy(
            maxAttempts: 1,
            baseDelay: 1.0,
            maxDelay: 30.0,
            multiplier: 2.0,
        );

        expect($strategy->shouldRetry())->toBeTrue();

        $strategy->recordFailure();

        expect($strategy->shouldRetry())->toBeFalse();
    });

    it('funziona con multiplier=1 (delay costante)', function () {
        $strategy = new ExponentialBackoffStrategy(
            maxAttempts: 3,
            baseDelay: 5.0,
            maxDelay: 30.0,
            multiplier: 1.0,
        );

        $strategy->recordFailure();
        expect($strategy->nextDelay())->toBe(5.0);

        $strategy->recordFailure();
        expect($strategy->nextDelay())->toBe(5.0);
    });
});
