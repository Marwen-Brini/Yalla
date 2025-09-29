<?php

declare(strict_types=1);

namespace Yalla\Commands\Traits;

use Yalla\Output\Output;

/**
 * Trait for adding dry-run support to commands
 *
 * Allows commands to simulate operations without making actual changes,
 * useful for testing and previewing operations in production environments
 */
trait DryRunnable
{
    /**
     * Whether dry run mode is enabled
     */
    protected bool $dryRun = false;

    /**
     * Log of operations that would have been performed
     */
    protected array $dryRunLog = [];

    /**
     * Output instance for displaying messages
     */
    protected ?Output $output = null;

    /**
     * Enable or disable dry run mode
     *
     * @param  bool  $enabled  Whether to enable dry run mode
     */
    public function setDryRun(bool $enabled = true): self
    {
        $this->dryRun = $enabled;

        if ($enabled && $this->output !== null) {
            $this->output->warning('🔍 DRY RUN MODE ENABLED');
            $this->output->info('No changes will be made to the system');
            $this->output->writeln('');
        }

        return $this;
    }

    /**
     * Check if dry run mode is enabled
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * Execute an operation or simulate it in dry run mode
     *
     * @param  string  $description  Description of the operation
     * @param  callable  $operation  The actual operation to perform
     * @param  array  $context  Additional context for logging
     * @return mixed Result of the operation or null in dry run mode
     */
    protected function executeOrSimulate(string $description, callable $operation, array $context = [])
    {
        if ($this->isDryRun()) {
            return $this->simulateOperation($description, $operation, $context);
        }

        return $this->executeOperation($description, $operation, $context);
    }

    /**
     * Simulate an operation without executing it
     *
     * @param  string  $description  Description of the operation
     * @param  callable  $operation  The operation (not executed in simulation)
     * @param  array  $context  Additional context for logging
     * @return mixed Simulated result or null
     */
    protected function simulateOperation(string $description, callable $operation, array $context = [])
    {
        if ($this->output !== null) {
            $this->output->info("[DRY RUN] Would execute: {$description}");
        }

        // Log the operation
        $this->dryRunLog[] = [
            'description' => $description,
            'context' => $context,
            'timestamp' => microtime(true),
        ];

        // Show context if verbose and output is available
        if ($this->output !== null && method_exists($this->output, 'isVerbose') && $this->output->isVerbose() && ! empty($context)) {
            foreach ($context as $key => $value) {
                $displayValue = $this->formatContextValue($value);
                $this->output->verbose("  → {$key}: {$displayValue}");
            }
        }

        // Try to get a sample result without side effects using transactions
        // @codeCoverageIgnoreStart
        if ($this->canSimulateWithTransaction($operation)) {
            return $this->simulateWithTransaction($operation);
        }
        // @codeCoverageIgnoreEnd

        return null;
    }

    /**
     * Format context value for display
     *
     * @param  mixed  $value  The value to format
     * @return string Formatted string
     */
    protected function formatContextValue($value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            return get_class($value);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    }

    /**
     * Check if operation can be simulated with transaction
     *
     * @param  callable  $operation  The operation to check
     * @codeCoverageIgnore Database-specific functionality
     */
    protected function canSimulateWithTransaction(callable $operation): bool
    {
        // Check if we have transaction support methods
        return method_exists($this, 'beginTransaction') &&
               method_exists($this, 'rollback');
    }

    /**
     * Simulate operation using transaction rollback
     *
     * @param  callable  $operation  The operation to simulate
     * @return mixed Result of the operation before rollback
     * @codeCoverageIgnore Database-specific functionality
     */
    protected function simulateWithTransaction(callable $operation)
    {
        try {
            $this->beginTransaction();
            $result = $operation();
            $this->rollback();

            return $result;
        } catch (\Exception $e) {
            if ($this->output !== null && method_exists($this->output, 'isDebug') && $this->output->isDebug()) {
                $this->output->debug('  → Simulation error: '.$e->getMessage());
            }

            // Try to rollback if possible
            if (method_exists($this, 'rollback')) {
                try {
                    $this->rollback();
                } catch (\Exception $rollbackException) {
                    // Ignore rollback errors in simulation
                }
            }

            return null;
        }
    }

    /**
     * Execute an operation with logging
     *
     * @param  string  $description  Description of the operation
     * @param  callable  $operation  The operation to perform
     * @param  array  $context  Additional context
     * @return mixed Result of the operation
     * @throws \Exception If operation fails
     */
    protected function executeOperation(string $description, callable $operation, array $context = [])
    {
        if ($this->output !== null && method_exists($this->output, 'isVerbose') && $this->output->isVerbose()) {
            $this->output->verbose("Executing: {$description}");
        }

        $startTime = microtime(true);

        try {
            $result = $operation();
            $duration = microtime(true) - $startTime;

            if ($this->output !== null && method_exists($this->output, 'isDebug') && $this->output->isDebug()) {
                $this->output->debug(sprintf('  → Completed in %.3fs', $duration));
            }

            return $result;

        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;

            if ($this->output !== null) {
                $this->output->error("Failed: {$description}");
                $this->output->error('  → Error: '.$e->getMessage());

                if (method_exists($this->output, 'isDebug') && $this->output->isDebug()) {
                    $this->output->debug(sprintf('  → Failed after %.3fs', $duration));
                    $this->output->debug('  → Stack trace:');
                    $this->output->debug($e->getTraceAsString());
                }
            }

            throw $e;
        }
    }

    /**
     * Get the dry run operation log
     *
     * @return array Array of logged operations
     */
    public function getDryRunLog(): array
    {
        return $this->dryRunLog;
    }

    /**
     * Get dry run summary
     *
     * @return array Summary information
     */
    public function getDryRunSummary(): array
    {
        return [
            'mode' => $this->dryRun ? 'dry-run' : 'execute',
            'operations' => count($this->dryRunLog),
            'log' => $this->dryRunLog,
        ];
    }

    /**
     * Display dry run summary
     */
    protected function showDryRunSummary(): void
    {
        if (! $this->isDryRun() || empty($this->dryRunLog) || $this->output === null) {
            return;
        }

        $this->output->writeln('');
        $this->output->section('Dry Run Summary');
        $this->output->info(sprintf(
            'Would have executed %d operation(s):',
            count($this->dryRunLog)
        ));

        foreach ($this->dryRunLog as $i => $entry) {
            $this->output->writeln(sprintf(
                '  %d. %s',
                $i + 1,
                $entry['description']
            ));

            // Show context if verbose
            if (method_exists($this->output, 'isVerbose') && $this->output->isVerbose() && ! empty($entry['context'])) {
                foreach ($entry['context'] as $key => $value) {
                    $displayValue = $this->formatContextValue($value);
                    $this->output->writeln(sprintf('     - %s: %s', $key, $displayValue));
                }
            }
        }

        $this->output->writeln('');
        $this->output->comment('💡 Remove --dry-run flag to execute these operations');
    }

    /**
     * Clear the dry run log
     */
    public function clearDryRunLog(): void
    {
        $this->dryRunLog = [];
    }

    /**
     * Set the output instance
     *
     * @param  Output  $output  The output instance
     */
    public function setDryRunOutput(Output $output): self
    {
        $this->output = $output;

        return $this;
    }
}
