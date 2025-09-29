<?php

declare(strict_types=1);

namespace Yalla\Commands\Traits;

use Yalla\Output\Output;
use Yalla\Process\Promise;

trait SupportsAsync
{
    protected int $asyncTimeout = 300; // 5 minutes default
    protected bool $runAsync = false;

    /**
     * Execute the command asynchronously
     *
     * @param array $input
     * @param Output $output
     * @return Promise
     */
    public function executeAsync(array $input, Output $output): Promise
    {
        $promise = new Promise(function() use ($input, $output) {
            try {
                $result = $this->execute($input, $output);
                return ['exitCode' => $result, 'output' => $output];
            } catch (\Throwable $e) {
                throw $e;
            }
        }, $this->asyncTimeout);

        // Show progress indicator if verbose
        if ($output->isVerbose()) {
            $promise->onProgress(function($progress) use ($output) {
                $output->write('.');
            });
        }

        return $promise;
    }

    /**
     * Check if the command should run asynchronously
     *
     * @param array $input
     * @return bool
     */
    public function shouldRunAsync(array $input): bool
    {
        // Check if --async option is present
        if (isset($input['options']['async']) && $input['options']['async']) {
            return true;
        }

        // Check if command is configured to always run async
        return $this->runAsync;
    }

    /**
     * Get the timeout for async execution in seconds
     *
     * @return int
     */
    public function getAsyncTimeout(): int
    {
        return $this->asyncTimeout;
    }

    /**
     * Set the timeout for async execution in seconds
     *
     * @param int $timeout
     * @return self
     */
    public function setAsyncTimeout(int $timeout): self
    {
        $this->asyncTimeout = $timeout;
        return $this;
    }

    /**
     * Handle async command completion
     *
     * @param mixed $result
     * @param Output $output
     * @return int Exit code
     */
    public function handleAsyncCompletion($result, Output $output): int
    {
        if (is_array($result) && isset($result['exitCode'])) {
            return $result['exitCode'];
        }

        return 0;
    }

    /**
     * Handle async command error
     *
     * @param \Throwable $exception
     * @param Output $output
     * @return int Exit code
     */
    public function handleAsyncError(\Throwable $exception, Output $output): int
    {
        $output->error('Async command failed: ' . $exception->getMessage());

        if ($output->isDebug()) {
            $output->writeln('Stack trace:');
            $output->writeln($exception->getTraceAsString());
        }

        // Map exception to exit code if method exists
        if (method_exists($this, 'mapExceptionToExitCode')) {
            return $this->mapExceptionToExitCode($exception);
        }

        return 1;
    }

    /**
     * Run multiple async operations in parallel
     *
     * @param array $operations Array of callables
     * @param Output $output
     * @return array Results
     */
    protected function runParallel(array $operations, Output $output): array
    {
        $promises = [];

        foreach ($operations as $key => $operation) {
            $promise = new Promise($operation, $this->asyncTimeout);
            $promises[$key] = $promise;
        }

        // Wait for all to complete
        $allPromise = Promise::all($promises);

        try {
            return $allPromise->wait();
        } catch (\Throwable $e) {
            $output->error('Parallel execution failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Configure async-related options
     *
     * @return void
     */
    protected function configureAsyncOptions(): void
    {
        if (!in_array('async', array_column($this->options ?? [], 'name'))) {
            $this->addOption('async', null, 'Run command asynchronously', false);
        }

        if (!in_array('timeout', array_column($this->options ?? [], 'name'))) {
            $this->addOption('timeout', null, 'Async execution timeout in seconds', $this->asyncTimeout);
        }
    }
}