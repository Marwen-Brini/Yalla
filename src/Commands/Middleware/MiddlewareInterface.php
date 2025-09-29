<?php

declare(strict_types=1);

namespace Yalla\Commands\Middleware;

use Yalla\Commands\Command;
use Yalla\Output\Output;

interface MiddlewareInterface
{
    /**
     * Handle the command execution
     *
     * @param Command $command The command being executed
     * @param array $input The input parameters
     * @param Output $output The output instance
     * @param \Closure $next The next middleware in the pipeline
     * @return int Exit code
     */
    public function handle(Command $command, array $input, Output $output, \Closure $next): int;

    /**
     * Get the priority of this middleware (higher = earlier execution)
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Check if this middleware should be applied to the given command
     *
     * @param Command $command
     * @param array $input
     * @return bool
     */
    public function shouldApply(Command $command, array $input): bool;
}