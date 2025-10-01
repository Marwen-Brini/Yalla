#!/usr/bin/env php
<?php

/**
 * Exit Codes Example
 *
 * This example demonstrates the use of exit codes in Yalla CLI commands
 */

require_once __DIR__.'/../vendor/autoload.php';

use Yalla\Commands\Command;
use Yalla\Output\Output;

/**
 * Example migration command using exit codes
 */
class MigrationCommand extends Command
{
    protected string $name = 'migrate';

    protected string $description = 'Run database migrations with proper exit codes';

    public function __construct()
    {
        $this->addOption('dry-run', 'd', 'Run in dry-run mode', false);
        $this->addOption('force', 'f', 'Force migration in production', false);
        $this->addOption('timeout', 't', 'Timeout in seconds', 60);
    }

    public function execute(array $input, Output $output): int
    {
        $this->setOutput($output);

        try {
            // Check environment
            if ($this->isProduction() && ! $this->getOption($input, 'force')) {
                $output->writeln($output->color('⚠️  Production environment detected!', Output::YELLOW));
                $this->exitError('Use --force to run migrations in production', self::EXIT_CANCELLED);
            }

            // Simulate lock check
            if ($this->isLocked()) {
                $output->writeln($output->color('Another migration is already running', Output::RED));

                return self::EXIT_LOCKED;
            }

            // Simulate timeout
            $timeout = (int) $this->getOption($input, 'timeout');
            if ($timeout < 10) {
                return self::EXIT_TIMEOUT;
            }

            // Simulate validation
            if (! $this->validateMigrations()) {
                return self::EXIT_VALIDATION;
            }

            // Run migrations
            $output->writeln('Running migrations...');

            if ($this->getOption($input, 'dry-run')) {
                $output->writeln($output->color('🔍 DRY RUN - No changes made', Output::CYAN));

                return self::EXIT_SUCCESS;
            }

            // Simulate successful migration
            $output->writeln($output->color('✅ All migrations completed successfully!', Output::GREEN));

            return self::EXIT_SUCCESS;

        } catch (\InvalidArgumentException $e) {
            return $this->handleException($e);
        } catch (\RuntimeException $e) {
            return $this->handleException($e);
        } catch (\Exception $e) {
            $output->writeln($output->color('❌ Unexpected error: '.$e->getMessage(), Output::RED));

            return self::EXIT_FAILURE;
        }
    }

    private function isProduction(): bool
    {
        return getenv('APP_ENV') === 'production';
    }

    private function isLocked(): bool
    {
        // Simulate random lock state
        return rand(0, 10) > 8;
    }

    private function validateMigrations(): bool
    {
        // Simulate validation
        return true;
    }
}

/**
 * Example command with various exit scenarios
 */
class ExitCodeDemoCommand extends Command
{
    protected string $name = 'exit-demo';

    protected string $description = 'Demonstrate all exit codes';

    public function __construct()
    {
        $this->addArgument('code', 'Exit code to demonstrate', false);
        $this->addOption('list', 'l', 'List all exit codes', false);
    }

    public function execute(array $input, Output $output): int
    {
        $this->setOutput($output);

        if ($this->getOption($input, 'list')) {
            return $this->listExitCodes($output);
        }

        $codeName = $this->getArgument($input, 'code');
        if (! $codeName) {
            $output->writeln('Usage: exit-demo <code> or exit-demo --list');

            return self::EXIT_USAGE;
        }

        return $this->demonstrateExitCode($codeName, $output);
    }

    private function listExitCodes(Output $output): int
    {
        $output->writeln($output->color('=== Yalla CLI Exit Codes ===', Output::CYAN));
        $output->writeln('');

        $codes = [
            'Standard POSIX Codes' => [
                self::EXIT_SUCCESS => 'Success',
                self::EXIT_FAILURE => 'General failure',
                self::EXIT_USAGE => 'Incorrect usage',
            ],
            'Data & I/O Errors' => [
                self::EXIT_DATAERR => 'Data format error',
                self::EXIT_NOINPUT => 'Cannot open input',
                self::EXIT_IOERR => 'I/O error',
                self::EXIT_CANTCREAT => 'Cannot create output',
            ],
            'System Errors' => [
                self::EXIT_SOFTWARE => 'Internal software error',
                self::EXIT_OSERR => 'System error',
                self::EXIT_OSFILE => 'System file missing',
                self::EXIT_TEMPFAIL => 'Temporary failure',
            ],
            'Permission & Config' => [
                self::EXIT_NOPERM => 'Permission denied',
                self::EXIT_CONFIG => 'Configuration error',
            ],
            'Migration-Specific' => [
                self::EXIT_LOCKED => 'Resource locked',
                self::EXIT_TIMEOUT => 'Operation timed out',
                self::EXIT_CANCELLED => 'Cancelled by user',
                self::EXIT_VALIDATION => 'Validation failed',
                self::EXIT_MISSING_DEPS => 'Missing dependencies',
                self::EXIT_NOT_FOUND => 'Resource not found',
                self::EXIT_CONFLICT => 'Resource conflict',
                self::EXIT_ROLLBACK => 'Operation rolled back',
                self::EXIT_PARTIAL => 'Partially completed',
            ],
            'Signal Codes' => [
                self::EXIT_SIGINT => 'Interrupted (Ctrl+C)',
                self::EXIT_SIGTERM => 'Terminated',
            ],
        ];

        foreach ($codes as $category => $categoryECodes) {
            $output->writeln($output->color("  {$category}:", Output::YELLOW));
            foreach ($categoryECodes as $code => $description) {
                $output->writeln(sprintf('    %3d - %s', $code, $description));
            }
            $output->writeln('');
        }

        return self::EXIT_SUCCESS;
    }

    private function demonstrateExitCode(string $codeName, Output $output): int
    {
        $codeMap = [
            'success' => self::EXIT_SUCCESS,
            'failure' => self::EXIT_FAILURE,
            'usage' => self::EXIT_USAGE,
            'locked' => self::EXIT_LOCKED,
            'timeout' => self::EXIT_TIMEOUT,
            'cancelled' => self::EXIT_CANCELLED,
            'validation' => self::EXIT_VALIDATION,
            'not-found' => self::EXIT_NOT_FOUND,
            'permission' => self::EXIT_NOPERM,
            'config' => self::EXIT_CONFIG,
        ];

        if (! isset($codeMap[$codeName])) {
            $output->writeln($output->color("Unknown code: {$codeName}", Output::RED));
            $output->writeln('Available codes: '.implode(', ', array_keys($codeMap)));

            return self::EXIT_USAGE;
        }

        $exitCode = $codeMap[$codeName];
        $description = self::getExitCodeDescription($exitCode);

        $output->writeln("Exiting with code {$exitCode}: {$description}");

        // Simulate different exit scenarios
        switch ($exitCode) {
            case self::EXIT_SUCCESS:
                $this->exitSuccess('✅ Operation completed successfully!');

                break;

            case self::EXIT_LOCKED:
                $this->exitError('❌ Resource is locked by another process', $exitCode);

                break;

            case self::EXIT_TIMEOUT:
                $this->exitError('⏱️ Operation timed out', $exitCode);

                break;

            case self::EXIT_VALIDATION:
                $this->exitError('❌ Validation failed: Invalid input data', $exitCode);

                break;

            default:
                return $exitCode;
        }
    }
}

// Run the examples
$output = new Output;

$output->writeln($output->color('=== Yalla CLI Exit Codes Example ===', Output::CYAN));
$output->writeln('');

// Example 1: List all exit codes
$demoCommand = new ExitCodeDemoCommand;
$exitCode = $demoCommand->execute(['options' => ['list' => true]], $output);

$output->writeln('');
$output->writeln($output->color('=== Testing Migration Command ===', Output::CYAN));
$output->writeln('');

// Example 2: Run migration command
$migrationCommand = new MigrationCommand;

// Test different scenarios
$scenarios = [
    ['dry-run' => true, 'force' => false, 'timeout' => 60],
    ['dry-run' => false, 'force' => true, 'timeout' => 60],
    ['dry-run' => false, 'force' => false, 'timeout' => 5],
];

foreach ($scenarios as $i => $options) {
    $output->writeln($output->color('Scenario '.($i + 1).':', Output::YELLOW));
    $output->writeln('Options: '.json_encode($options));

    $exitCode = $migrationCommand->execute(['options' => $options], $output);

    $description = Command::getExitCodeDescription($exitCode);
    $color = $exitCode === 0 ? Output::GREEN : Output::RED;
    $output->writeln($output->color("Exit code: {$exitCode} ({$description})", $color));
    $output->writeln('');
}

// Example 3: Exception handling
$output->writeln($output->color('=== Exception Handling Example ===', Output::CYAN));
$output->writeln('');

class ExceptionTestCommand extends Command
{
    protected string $name = 'exception-test';

    protected string $description = 'Test exception handling';

    public function execute(array $input, Output $output): int
    {
        $this->setOutput($output);

        try {
            throw new \InvalidArgumentException('Invalid configuration provided');
        } catch (\Throwable $e) {
            $output->writeln('Caught exception: '.get_class($e));
            $code = $this->mapExceptionToExitCode($e);
            $description = self::getExitCodeDescription($code);
            $output->writeln($output->color("Mapped to exit code: {$code} ({$description})", Output::YELLOW));

            return $code;
        }
    }
}

$exceptionCommand = new ExceptionTestCommand;
$exitCode = $exceptionCommand->execute([], $output);

$output->writeln('');
$output->writeln($output->color('✅ Exit codes example completed!', Output::GREEN));
$output->writeln('');
$output->writeln('Exit codes are essential for:');
$output->writeln('  • CI/CD pipelines');
$output->writeln('  • Shell scripting');
$output->writeln('  • Error handling');
$output->writeln('  • Process monitoring');
