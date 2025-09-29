<?php

declare(strict_types=1);

namespace Yalla\Process;

class Promise
{
    private $process;
    private $onFulfilled = [];
    private $onRejected = [];
    private $onProgress = [];
    private $state = 'pending'; // pending, fulfilled, rejected
    private $result;
    private $error;
    private int $timeout;
    private float $startTime;

    public function __construct($process, int $timeout = 0)
    {
        $this->process = $process;
        $this->timeout = $timeout;
        $this->startTime = microtime(true);
    }

    /**
     * Add a fulfillment handler
     *
     * @param callable $callback
     * @return self
     */
    public function then(callable $callback): self
    {
        if ($this->state === 'fulfilled') {
            $callback($this->result);
        } else {
            $this->onFulfilled[] = $callback;
        }
        return $this;
    }

    /**
     * Add a rejection handler
     *
     * @param callable $callback
     * @return self
     */
    public function catch(callable $callback): self
    {
        if ($this->state === 'rejected') {
            $callback($this->error);
        } else {
            $this->onRejected[] = $callback;
        }
        return $this;
    }

    /**
     * Add both fulfillment and rejection handlers
     *
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return self
     */
    public function finally(callable $callback): self
    {
        $wrapper = function($value) use ($callback) {
            $callback();
            return $value;
        };

        return $this->then($wrapper)->catch(function($error) use ($callback) {
            $callback();
            throw $error;
        });
    }

    /**
     * Add a progress handler
     *
     * @param callable $callback
     * @return self
     */
    public function onProgress(callable $callback): self
    {
        $this->onProgress[] = $callback;
        return $this;
    }

    /**
     * Resolve the promise with a value
     *
     * @param mixed $value
     * @return void
     */
    public function resolve($value): void
    {
        if ($this->state !== 'pending') {
            return;
        }

        $this->state = 'fulfilled';
        $this->result = $value;

        foreach ($this->onFulfilled as $callback) {
            $callback($value);
        }
    }

    /**
     * Reject the promise with an error
     *
     * @param \Throwable $error
     * @return void
     */
    public function reject(\Throwable $error): void
    {
        if ($this->state !== 'pending') {
            return;
        }

        $this->state = 'rejected';
        $this->error = $error;

        foreach ($this->onRejected as $callback) {
            $callback($error);
        }
    }

    /**
     * Report progress
     *
     * @param mixed $progress
     * @return void
     */
    public function progress($progress): void
    {
        foreach ($this->onProgress as $callback) {
            $callback($progress);
        }
    }

    /**
     * Check if the promise is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state === 'pending';
    }

    /**
     * Check if the promise is fulfilled
     *
     * @return bool
     */
    public function isFulfilled(): bool
    {
        return $this->state === 'fulfilled';
    }

    /**
     * Check if the promise is rejected
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->state === 'rejected';
    }

    /**
     * Get the state of the promise
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Get the result if fulfilled
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Get the error if rejected
     *
     * @return \Throwable|null
     */
    public function getError(): ?\Throwable
    {
        return $this->error;
    }

    /**
     * Wait for the promise to settle
     *
     * @param int $pollInterval Microseconds between polls
     * @return mixed
     * @throws \Throwable
     */
    public function wait(int $pollInterval = 100000)
    {
        $maxWaitTime = 10; // Maximum 10 seconds wait even without timeout
        $waitStart = microtime(true);

        while ($this->isPending()) {
            // Check timeout
            if ($this->timeout > 0 && (microtime(true) - $this->startTime) > $this->timeout) {
                $this->reject(new \RuntimeException('Promise timed out'));
                break;
            }

            // Safety check: prevent infinite wait
            if ((microtime(true) - $waitStart) > $maxWaitTime) {
                $this->reject(new \RuntimeException('Promise wait exceeded maximum time'));
                break;
            }

            // Poll the process if it's callable
            if (is_callable($this->process)) {
                try {
                    $result = ($this->process)();
                    if ($result !== null) {
                        $this->resolve($result);
                    }
                } catch (\Throwable $e) {
                    $this->reject($e);
                }
            }

            usleep($pollInterval);
        }

        if ($this->isRejected()) {
            throw $this->error;
        }

        return $this->result;
    }

    /**
     * Create a promise that resolves with the given value
     *
     * @param mixed $value
     * @return self
     */
    public static function resolved($value): self
    {
        $promise = new self(null);
        $promise->resolve($value);
        return $promise;
    }

    /**
     * Create a promise that rejects with the given error
     *
     * @param \Throwable $error
     * @return self
     */
    public static function rejected(\Throwable $error): self
    {
        $promise = new self(null);
        $promise->reject($error);
        return $promise;
    }

    /**
     * Create a promise that resolves when all promises resolve
     *
     * @param array $promises
     * @return self
     */
    public static function all(array $promises): self
    {
        $results = [];
        $remaining = count($promises);
        $promise = new self(null);

        if ($remaining === 0) {
            $promise->resolve([]);
            return $promise;
        }

        foreach ($promises as $key => $p) {
            $p->then(function($value) use ($key, &$results, &$remaining, $promise) {
                $results[$key] = $value;
                $remaining--;

                if ($remaining === 0) {
                    $promise->resolve($results);
                }
            })->catch(function($error) use ($promise) {
                $promise->reject($error);
            });
        }

        return $promise;
    }

    /**
     * Create a promise that resolves with the first settled promise
     *
     * @param array $promises
     * @return self
     */
    public static function race(array $promises): self
    {
        $promise = new self(null);

        foreach ($promises as $p) {
            $p->then(function($value) use ($promise) {
                $promise->resolve($value);
            })->catch(function($error) use ($promise) {
                $promise->reject($error);
            });
        }

        return $promise;
    }
}