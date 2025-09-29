<?php

declare(strict_types=1);

namespace Yalla\Commands\Middleware;

use Yalla\Commands\Command;
use Yalla\Output\Output;

class TimingMiddleware implements MiddlewareInterface
{
    private int $priority;
    private bool $showTiming;

    public function __construct(bool $showTiming = true, int $priority = 90)
    {
        $this->showTiming = $showTiming;
        $this->priority = $priority;
    }

    /**
     * Handle the command execution with timing
     *
     * @param Command $command
     * @param array $input
     * @param Output $output
     * @param \Closure $next
     * @return int
     */
    public function handle(Command $command, array $input, Output $output, \Closure $next): int
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Execute the command
        $exitCode = $next($command, $input, $output);

        if ($this->showTiming && $output->isVerbose()) {
            $duration = microtime(true) - $startTime;
            $memoryUsed = memory_get_usage(true) - $startMemory;
            $peakMemory = memory_get_peak_usage(true);

            $output->writeln('');
            $output->writeln($output->color('Timing Information:', Output::CYAN));
            $output->writeln(sprintf('  Execution time: %.3f seconds', $duration));
            $output->writeln(sprintf('  Memory used: %s', $this->formatBytes($memoryUsed)));
            $output->writeln(sprintf('  Peak memory: %s', $this->formatBytes($peakMemory)));
        }

        return $exitCode;
    }

    /**
     * Get the priority of this middleware
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Check if this middleware should be applied
     *
     * @param Command $command
     * @param array $input
     * @return bool
     */
    public function shouldApply(Command $command, array $input): bool
    {
        // Apply to all commands when timing is requested
        return isset($input['options']['time']) || isset($input['options']['timing']);
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}