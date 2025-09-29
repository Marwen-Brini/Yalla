<?php

declare(strict_types=1);

namespace Yalla\Commands;

use Yalla\Output\Output;
use Yalla\Process\Promise;

interface AsyncCommandInterface
{
    /**
     * Execute the command asynchronously
     *
     * @param array $input
     * @param Output $output
     * @return Promise
     */
    public function executeAsync(array $input, Output $output): Promise;

    /**
     * Check if the command should run asynchronously
     *
     * @param array $input
     * @return bool
     */
    public function shouldRunAsync(array $input): bool;

    /**
     * Get the timeout for async execution in seconds
     *
     * @return int
     */
    public function getAsyncTimeout(): int;

    /**
     * Handle async command completion
     *
     * @param mixed $result
     * @param Output $output
     * @return int Exit code
     */
    public function handleAsyncCompletion($result, Output $output): int;

    /**
     * Handle async command error
     *
     * @param \Throwable $exception
     * @param Output $output
     * @return int Exit code
     */
    public function handleAsyncError(\Throwable $exception, Output $output): int;
}