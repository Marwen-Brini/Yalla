<?php

declare(strict_types=1);

use Yalla\Process\Promise;

test('promise resolve', function () {
    $promise = new Promise(null);
    $result = null;

    $promise->then(function($value) use (&$result) {
        $result = $value;
    });

    $promise->resolve('success');
    expect($result)->toBe('success');
    expect($promise->isFulfilled())->toBeTrue();
    expect($promise->isPending())->toBeFalse();
});

test('promise reject', function () {
    $promise = new Promise(null);
    $error = null;

    $promise->catch(function($e) use (&$error) {
        $error = $e;
    });

    $exception = new \RuntimeException('error');
    $promise->reject($exception);

    expect($error)->toBe($exception);
    expect($promise->isRejected())->toBeTrue();
    expect($promise->isPending())->toBeFalse();
});

test('promise finally', function () {
    $promise = new Promise(null);
    $finallyCalled = false;

    $promise->finally(function() use (&$finallyCalled) {
        $finallyCalled = true;
    });

    $promise->resolve('done');
    expect($finallyCalled)->toBeTrue();
});

test('promise progress', function () {
    $promise = new Promise(null);
    $progressValues = [];

    $promise->onProgress(function($value) use (&$progressValues) {
        $progressValues[] = $value;
    });

    $promise->progress(25);
    $promise->progress(50);
    $promise->progress(75);
    $promise->progress(100);

    expect($progressValues)->toBe([25, 50, 75, 100]);
});

test('promise chaining', function () {
    $promise = new Promise(null);
    $results = [];

    $promise
        ->then(function($value) use (&$results) {
            $results[] = "First: $value";
            return $value;
        })
        ->then(function($value) use (&$results) {
            $results[] = "Second: $value";
        });

    $promise->resolve('test');

    expect($results)->toBe(['First: test', 'Second: test']);
});

test('promise wait', function () {
    $callCount = 0;
    $promise = new Promise(function() use (&$callCount) {
        $callCount++;
        if ($callCount >= 3) {
            return 'completed';
        }
        return null;
    });

    $result = $promise->wait(1000); // 1ms poll interval
    expect($result)->toBe('completed');
});

test('promise wait timeout', function () {
    $promise = new Promise(function() {
        return null; // Never resolves
    }, 1); // 1 second timeout

    expect(fn() => $promise->wait(10000))
        ->toThrow(\RuntimeException::class, 'Promise timed out');
});

test('static resolved', function () {
    $promise = Promise::resolved('value');
    expect($promise->isFulfilled())->toBeTrue();
    expect($promise->getResult())->toBe('value');
});

test('static rejected', function () {
    $exception = new \RuntimeException('error');
    $promise = Promise::rejected($exception);
    expect($promise->isRejected())->toBeTrue();
    expect($promise->getError())->toBe($exception);
});

test('promise all', function () {
    $promise1 = Promise::resolved(1);
    $promise2 = Promise::resolved(2);
    $promise3 = Promise::resolved(3);

    $all = Promise::all([$promise1, $promise2, $promise3]);
    $result = $all->wait();

    expect($result)->toBe([1, 2, 3]);
});

test('promise all empty', function () {
    $all = Promise::all([]);
    $result = $all->wait();
    expect($result)->toBe([]);
});

test('promise all reject', function () {
    $promise1 = Promise::resolved(1);
    $promise2 = Promise::rejected(new \RuntimeException('error'));
    $promise3 = Promise::resolved(3);

    $all = Promise::all([$promise1, $promise2, $promise3]);

    expect(fn() => $all->wait())
        ->toThrow(\RuntimeException::class, 'error');
});

test('promise race', function () {
    $promise1 = new Promise(null);
    $promise2 = new Promise(null);
    $promise3 = new Promise(null);

    $race = Promise::race([$promise1, $promise2, $promise3]);

    $promise2->resolve('second wins');
    $result = $race->wait();

    expect($result)->toBe('second wins');
});

test('promise state', function () {
    $promise = new Promise(null);
    expect($promise->getState())->toBe('pending');

    $promise->resolve('done');
    expect($promise->getState())->toBe('fulfilled');

    $promise2 = new Promise(null);
    $promise2->reject(new \RuntimeException('error'));
    expect($promise2->getState())->toBe('rejected');
});

test('promise double resolve', function () {
    $promise = new Promise(null);
    $results = [];

    $promise->then(function($value) use (&$results) {
        $results[] = $value;
    });

    $promise->resolve('first');
    $promise->resolve('second'); // Should be ignored

    expect($results)->toBe(['first']);
});

test('promise double reject', function () {
    $promise = new Promise(null);
    $errors = [];

    $promise->catch(function($e) use (&$errors) {
        $errors[] = $e->getMessage();
    });

    $promise->reject(new \RuntimeException('first'));
    $promise->reject(new \RuntimeException('second')); // Should be ignored

    expect($errors)->toBe(['first']);
});