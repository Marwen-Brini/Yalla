<?php

declare(strict_types=1);

namespace Yalla\Process;

use Yalla\Commands\AsyncCommandInterface;
use Yalla\Output\Output;

class AsyncExecutor
{
    private array $runningCommands = [];

    private int $maxConcurrent = 10;

    /**
     * Execute a command asynchronously
     */
    public function execute(AsyncCommandInterface $command, array $input, Output $output): Promise
    {
        // Check if we should run async
        if (! $command->shouldRunAsync($input)) {
            // Run synchronously and wrap in resolved promise
            $result = $command->execute($input, $output);

            return Promise::resolved(['exitCode' => $result]);
        }

        // Check concurrent limit
        if (count($this->runningCommands) >= $this->maxConcurrent) {
            throw new \RuntimeException("Maximum concurrent commands ({$this->maxConcurrent}) reached");
        }

        // Create and execute promise
        $promise = $command->executeAsync($input, $output);

        // Track running command
        $commandId = spl_object_id($command);
        $this->runningCommands[$commandId] = $promise;

        // Remove from running when done
        $promise->finally(function () use ($commandId) {
            unset($this->runningCommands[$commandId]);
        });

        // Handle completion and errors
        $promise
            ->then(function ($result) use ($command, $output) {
                return $command->handleAsyncCompletion($result, $output);
            })
            ->catch(function ($error) use ($command, $output) {
                return $command->handleAsyncError($error, $output);
            });

        return $promise;
    }

    /**
     * Execute multiple commands in parallel
     *
     * @param  array  $commands  Array of [command, input] pairs
     */
    public function executeParallel(array $commands, Output $output): Promise
    {
        $promises = [];

        foreach ($commands as $key => [$command, $input]) {
            if (! $command instanceof AsyncCommandInterface) {
                throw new \InvalidArgumentException('Command must implement AsyncCommandInterface');
            }

            $promises[$key] = $this->execute($command, $input, $output);
        }

        return Promise::all($promises);
    }

    /**
     * Wait for all running commands to complete
     *
     * @return array Results
     */
    public function waitAll(): array
    {
        $results = [];

        foreach ($this->runningCommands as $id => $promise) {
            try {
                $results[$id] = $promise->wait();
            } catch (\Throwable $e) {
                $results[$id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Cancel all running commands
     */
    public function cancelAll(): void
    {
        foreach ($this->runningCommands as $promise) {
            if ($promise->isPending()) {
                $promise->reject(new \RuntimeException('Command cancelled'));
            }
        }
        $this->runningCommands = [];
    }

    /**
     * Get the number of running commands
     */
    public function getRunningCount(): int
    {
        return count($this->runningCommands);
    }

    /**
     * Set the maximum number of concurrent commands
     */
    public function setMaxConcurrent(int $max): self
    {
        $this->maxConcurrent = $max;

        return $this;
    }

    /**
     * Get the maximum number of concurrent commands
     */
    public function getMaxConcurrent(): int
    {
        return $this->maxConcurrent;
    }
}
