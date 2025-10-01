#!/usr/bin/env php
<?php

/**
 * Example: Command Middleware System
 *
 * This example demonstrates how to use middleware with commands
 * for cross-cutting concerns like logging, authentication, and timing.
 */

require_once __DIR__.'/../vendor/autoload.php';

use Yalla\Application;
use Yalla\Commands\Command;
use Yalla\Commands\Middleware\AuthenticationMiddleware;
use Yalla\Commands\Middleware\LoggingMiddleware;
use Yalla\Commands\Middleware\MiddlewareInterface;
use Yalla\Commands\Middleware\TimingMiddleware;
use Yalla\Commands\Traits\HasMiddleware;
use Yalla\Output\Output;

// Example 1: Command with logging middleware
class ImportDataCommand extends Command
{
    use HasMiddleware;

    protected string $name = 'import:data';

    protected string $description = 'Import data with logging';

    public function __construct()
    {
        $this->addArgument('source', 'Data source file', true);
        $this->addOption('format', 'f', 'Data format (csv, json, xml)', 'csv');

        // Add logging middleware
        $this->middleware(new LoggingMiddleware('import.log'));
    }

    public function execute(array $input, Output $output): int
    {
        return $this->executeWithMiddleware($input, $output, function ($cmd, $in, $out) {
            $source = $this->getArgument($in, 'source');
            $format = $this->getOption($in, 'format', 'csv');

            $out->info("Importing data from: {$source}");
            $out->info("Format: {$format}");

            // Simulate import
            sleep(1);

            $out->success('Data imported successfully!');

            return 0;
        });
    }
}

// Example 2: Command with authentication middleware
class AdminCommand extends Command
{
    use HasMiddleware;

    protected string $name = 'admin:users';

    protected string $description = 'Manage users (requires authentication)';

    public function __construct()
    {
        $this->addOption('list', 'l', 'List all users', false);
        $this->addOption('delete', 'd', 'Delete user by ID', null);
        $this->addOption('token', 't', 'Authentication token', null);

        // Configure authentication middleware
        $authMiddleware = new AuthenticationMiddleware;
        $authMiddleware->protect('admin:users');

        // Custom authentication callback
        $authMiddleware->setAuthCallback(function ($input, $output) {
            $token = $input['options']['token'] ?? null;

            if ($token === 'admin-secret-token') {
                $output->success('Authentication successful');

                return true;
            }

            return false;
        });

        $this->middleware($authMiddleware);
    }

    public function execute(array $input, Output $output): int
    {
        return $this->executeWithMiddleware($input, $output, function ($cmd, $in, $out) {
            $list = $this->getOption($in, 'list', false);
            $deleteId = $this->getOption($in, 'delete');

            if ($list) {
                $out->info('User List:');
                $out->writeln('  1. admin@example.com');
                $out->writeln('  2. user@example.com');
                $out->writeln('  3. test@example.com');
            }

            if ($deleteId) {
                $out->warning("Would delete user with ID: {$deleteId}");
            }

            return 0;
        });
    }
}

// Example 3: Command with timing middleware
class PerformanceTestCommand extends Command
{
    use HasMiddleware;

    protected string $name = 'test:performance';

    protected string $description = 'Test performance with timing middleware';

    public function __construct()
    {
        $this->addOption('iterations', 'i', 'Number of iterations', 1000);
        $this->addOption('time', null, 'Show timing information', false);

        // Add timing middleware
        $this->middleware(new TimingMiddleware(true));
    }

    public function execute(array $input, Output $output): int
    {
        return $this->executeWithMiddleware($input, $output, function ($cmd, $in, $out) {
            $iterations = (int) $this->getOption($in, 'iterations', 1000);

            $out->info("Running {$iterations} iterations...");

            $result = 0;
            for ($i = 0; $i < $iterations; $i++) {
                $result += sqrt($i) * pi();
            }

            $out->success('Completed! Result: '.round($result, 2));

            return 0;
        });
    }
}

// Example 4: Custom middleware implementation
class ValidationMiddleware implements MiddlewareInterface
{
    private array $rules = [];

    public function addRule(string $field, callable $validator): self
    {
        $this->rules[$field] = $validator;

        return $this;
    }

    public function handle(Command $command, array $input, Output $output, \Closure $next): int
    {
        $output->info('Validating input...');

        foreach ($this->rules as $field => $validator) {
            $value = $input['options'][$field] ?? $input['arguments'][$field] ?? null;

            if (! $validator($value)) {
                $output->error("Validation failed for field: {$field}");

                return 1;
            }
        }

        $output->success('Validation passed');

        return $next($command, $input, $output);
    }

    public function getPriority(): int
    {
        return 150; // Run before most middleware
    }

    public function shouldApply(Command $command, array $input): bool
    {
        return true;
    }
}

// Example 5: Command with multiple middleware
class ComplexCommand extends Command
{
    use HasMiddleware;

    protected string $name = 'complex:operation';

    protected string $description = 'Complex operation with multiple middleware';

    public function __construct()
    {
        $this->addArgument('input', 'Input file', true);
        $this->addOption('output', 'o', 'Output file', 'output.txt');
        $this->addOption('validate', null, 'Validate input', false);
        $this->addOption('time', null, 'Show timing', false);
        $this->addOption('log', null, 'Enable logging', false);

        // Add multiple middleware
        $this->middlewares([
            new LoggingMiddleware('complex.log'),
            new TimingMiddleware(true),
            $this->createValidationMiddleware(),
        ]);
    }

    private function createValidationMiddleware(): ValidationMiddleware
    {
        $validator = new ValidationMiddleware;

        $validator->addRule('input', function ($value) {
            return ! empty($value) && strlen($value) > 0;
        });

        $validator->addRule('output', function ($value) {
            return ! empty($value) && preg_match('/\.(txt|log|csv)$/', $value);
        });

        return $validator;
    }

    public function execute(array $input, Output $output): int
    {
        return $this->executeWithMiddleware($input, $output, function ($cmd, $in, $out) {
            $inputFile = $this->getArgument($in, 'input');
            $outputFile = $this->getOption($in, 'output', 'output.txt');

            $out->info("Processing: {$inputFile}");
            $out->info("Output to: {$outputFile}");

            // Simulate complex operation
            $out->writeln('');
            $steps = ['Reading', 'Parsing', 'Transforming', 'Writing'];

            foreach ($steps as $step) {
                $out->write("  {$step}... ");
                usleep(500000); // 0.5 seconds
                $out->writeln($out->color('✓', Output::GREEN));
            }

            $out->writeln('');
            $out->success('Operation completed successfully!');

            return 0;
        });
    }
}

// Example 6: Rate limiting middleware
class RateLimitMiddleware implements MiddlewareInterface
{
    private static array $requests = [];

    private int $maxRequests;

    private int $timeWindow;

    public function __construct(int $maxRequests = 10, int $timeWindow = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }

    public function handle(Command $command, array $input, Output $output, \Closure $next): int
    {
        $commandName = $command->getName();
        $now = time();

        // Clean old requests
        if (isset(self::$requests[$commandName])) {
            self::$requests[$commandName] = array_filter(
                self::$requests[$commandName],
                fn ($time) => ($now - $time) < $this->timeWindow
            );
        } else {
            self::$requests[$commandName] = [];
        }

        // Check rate limit
        if (count(self::$requests[$commandName]) >= $this->maxRequests) {
            $output->error("Rate limit exceeded for command: {$commandName}");
            $output->warning("Max {$this->maxRequests} requests per {$this->timeWindow} seconds");

            return 429; // Too Many Requests
        }

        // Record this request
        self::$requests[$commandName][] = $now;

        return $next($command, $input, $output);
    }

    public function getPriority(): int
    {
        return 200; // High priority - check rate limit early
    }

    public function shouldApply(Command $command, array $input): bool
    {
        return true;
    }
}

// Example 7: Command with rate limiting
class ApiCommand extends Command
{
    use HasMiddleware;

    protected string $name = 'api:call';

    protected string $description = 'Make API call with rate limiting';

    public function __construct()
    {
        $this->addArgument('endpoint', 'API endpoint', true);
        $this->addOption('method', 'm', 'HTTP method', 'GET');

        // Add rate limiting: max 3 requests per 10 seconds
        $this->middleware(new RateLimitMiddleware(3, 10));
    }

    public function execute(array $input, Output $output): int
    {
        return $this->executeWithMiddleware($input, $output, function ($cmd, $in, $out) {
            $endpoint = $this->getArgument($in, 'endpoint');
            $method = $this->getOption($in, 'method', 'GET');

            $out->info("Calling API: {$method} {$endpoint}");

            // Simulate API call
            sleep(1);

            $out->success('API call successful!');

            return 0;
        });
    }
}

// Create and run the application
$app = new Application('Middleware Example', '1.0.0');

// Register commands
$app->register(new ImportDataCommand);
$app->register(new AdminCommand);
$app->register(new PerformanceTestCommand);
$app->register(new ComplexCommand);
$app->register(new ApiCommand);

// Run the application
exit($app->run());
