<?php

declare(strict_types=1);

use Yalla\Process\Promise;

test('finally method with promise rejection covers lines 73-74', function () {
    $callbackExecuted = false;

    // Create a promise that will be rejected using the constructor
    $promise = new Promise(function() {
        throw new RuntimeException('Test error');
    });

    // Add a finally callback that should execute even when promise is rejected
    $finallyPromise = $promise->finally(function() use (&$callbackExecuted) {
        $callbackExecuted = true;
    });

    // The promise should be rejected, which should trigger lines 73-74
    expect(function() use ($finallyPromise) {
        $finallyPromise->wait(100000);
    })->toThrow(RuntimeException::class);

    // The finally callback should have been executed
    expect($callbackExecuted)->toBeTrue();
});

test('Promise race with rejection covers line 323', function () {
    // Create a pending promise and an immediately rejected one
    $promise1 = new Promise(function() {
        // This will never complete
        return null;
    });

    $promise2 = new Promise(function() {
        throw new RuntimeException('Race rejection');
    });

    // Manually reject promise2 to ensure it's already rejected when race starts
    $promise2->reject(new RuntimeException('Race rejection'));

    // Promise::race should reject when the first rejected promise settles (line 323)
    expect(function() use ($promise1, $promise2) {
        $racePromise = Promise::race([$promise1, $promise2]);
        $racePromise->wait(100000);
    })->toThrow(RuntimeException::class);
});

test('finally method with successful promise', function () {
    $callbackExecuted = false;

    // Create a promise that will be resolved successfully
    $promise = Promise::resolved('success');

    // Add a finally callback
    $finallyPromise = $promise->finally(function() use (&$callbackExecuted) {
        $callbackExecuted = true;
    });

    // Wait for the promise to complete
    $result = $finallyPromise->wait(100000);

    // The finally callback should have been executed
    expect($callbackExecuted)->toBeTrue();
    expect($result)->toBe('success');
});

test('wait method with immediate resolution', function () {
    // Create a promise that resolves immediately
    $promise = Promise::resolved('immediate result');

    // Wait should return the result immediately
    $result = $promise->wait(100000);
    expect($result)->toBe('immediate result');
});

test('wait method with immediate rejection', function () {
    // Create a promise that rejects immediately
    $promise = Promise::rejected(new RuntimeException('immediate error'));

    // Wait should throw the error immediately
    expect(function() use ($promise) {
        $promise->wait(100000);
    })->toThrow(RuntimeException::class, 'immediate error');
});

test('Promise all with rejection', function () {
    // Create promises where one will be rejected
    $promise1 = Promise::resolved('success');

    $promise2 = new Promise(function() {
        throw new RuntimeException('Promise 2 failed');
    });
    // Manually reject promise2 to ensure it's already rejected when all starts
    $promise2->reject(new RuntimeException('Promise 2 failed'));

    $promise3 = Promise::resolved('also success');

    // Promise::all should reject when any promise rejects
    expect(function() use ($promise1, $promise2, $promise3) {
        $allPromise = Promise::all([$promise1, $promise2, $promise3]);
        $allPromise->wait(100000);
    })->toThrow(RuntimeException::class);
});