<?php

declare(strict_types=1);

namespace Yalla\Commands\Middleware;

use Yalla\Commands\Command;
use Yalla\Output\Output;

class LoggingMiddleware implements MiddlewareInterface
{
    private string $logFile;
    private int $priority;

    public function __construct(string $logFile = 'commands.log', int $priority = 100)
    {
        $this->logFile = $logFile;
        $this->priority = $priority;
    }

    /**
     * Handle the command execution with logging
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
        $commandName = $command->getName();
        $timestamp = date('Y-m-d H:i:s');

        // Log command start
        $this->log("[{$timestamp}] Starting command: {$commandName}");
        $this->log("Input: " . json_encode($input));

        try {
            // Execute the command
            $exitCode = $next($command, $input, $output);

            $duration = round(microtime(true) - $startTime, 3);

            // Log command completion
            $this->log("[{$timestamp}] Completed command: {$commandName} (Exit code: {$exitCode}, Duration: {$duration}s)");

            return $exitCode;

        } catch (\Throwable $e) {
            $duration = round(microtime(true) - $startTime, 3);

            // Log command failure
            $this->log("[{$timestamp}] Failed command: {$commandName} (Duration: {$duration}s)");
            $this->log("Error: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());

            throw $e;
        }
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
        // Apply to all commands by default
        // Can be overridden to filter specific commands
        return true;
    }

    /**
     * Log a message to the log file
     *
     * @param string $message
     * @return void
     */
    private function log(string $message): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents($this->logFile, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}