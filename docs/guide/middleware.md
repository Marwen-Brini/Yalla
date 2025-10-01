# Command Middleware

::: tip New in v2.0
The middleware system is a new feature in Yalla CLI v2.0 that allows you to add authentication, logging, timing, and custom processing layers to your commands.
:::

## Overview

Middleware provides a convenient mechanism to filter and modify command execution. You can use middleware for authentication, logging, timing, validation, and more.

## Basic Usage

### Adding Middleware to Commands

```php
<?php

use Yalla\Commands\Command;
use Yalla\Commands\Traits\HasMiddleware;
use Yalla\Commands\Middleware\TimingMiddleware;
use Yalla\Commands\Middleware\LoggingMiddleware;
use Yalla\Output\Output;

class DeployCommand extends Command
{
    use HasMiddleware;

    public function __construct()
    {
        parent::__construct();

        $this->name = 'deploy';
        $this->description = 'Deploy the application';

        // Add middleware
        $this->middleware(new TimingMiddleware())
             ->middleware(new LoggingMiddleware());
    }

    public function execute(array $input, Output $output): int
    {
        $output->info('Deploying application...');
        // Deployment logic
        return 0;
    }
}
```

## Built-in Middleware

### TimingMiddleware

Measures command execution time:

```php
use Yalla\Commands\Middleware\TimingMiddleware;

$this->middleware(new TimingMiddleware());

// Output:
// Command took 2.5 seconds to execute
```

### LoggingMiddleware

Logs command execution details:

```php
use Yalla\Commands\Middleware\LoggingMiddleware;

$this->middleware(new LoggingMiddleware());

// Logs:
// [2025-10-01 10:30:45] Command 'deploy' started
// [2025-10-01 10:30:47] Command 'deploy' completed with exit code 0
```

### AuthenticationMiddleware

Example authentication middleware:

```php
use Yalla\Commands\Middleware\AuthenticationMiddleware;

$this->middleware(new AuthenticationMiddleware('your-secret-token'));
```

## Creating Custom Middleware

### Basic Middleware

Implement the `MiddlewareInterface`:

```php
<?php

namespace App\Middleware;

use Yalla\Commands\Command;
use Yalla\Commands\Middleware\MiddlewareInterface;
use Yalla\Output\Output;

class ValidationMiddleware implements MiddlewareInterface
{
    public function handle(Command $command, array $input, Output $output, callable $next): int
    {
        // Before command execution
        if (!$this->validate($input)) {
            $output->error('Validation failed');
            return 1;
        }

        // Execute command
        $exitCode = $next($command, $input, $output);

        // After command execution
        $output->info('Validation passed');

        return $exitCode;
    }

    public function getPriority(): int
    {
        return 100; // Higher priority runs first
    }

    protected function validate(array $input): bool
    {
        // Validation logic
        return true;
    }
}
```

### Using Custom Middleware

```php
class MyCommand extends Command
{
    use HasMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(new ValidationMiddleware());
    }
}
```

## Middleware Priority

Middleware executes in order of priority (higher first):

```php
// This middleware runs first (priority 200)
class AuthMiddleware implements MiddlewareInterface
{
    public function getPriority(): int
    {
        return 200;
    }
}

// This middleware runs second (priority 100)
class LoggingMiddleware implements MiddlewareInterface
{
    public function getPriority(): int
    {
        return 100;
    }
}

// Usage
$this->middleware(new LoggingMiddleware()) // Runs second
     ->middleware(new AuthMiddleware());    // Runs first
```

## Conditional Middleware

Apply middleware conditionally:

```php
class ConditionalMiddleware implements MiddlewareInterface
{
    public function __construct(
        private bool $condition = true
    ) {}

    public function handle(Command $command, array $input, Output $output, callable $next): int
    {
        if (!$this->condition) {
            // Skip this middleware
            return $next($command, $input, $output);
        }

        // Execute middleware logic
        $output->info('Conditional middleware executed');

        return $next($command, $input, $output);
    }

    public function getPriority(): int
    {
        return 50;
    }
}

// Usage
$this->middleware(new ConditionalMiddleware($someCondition));
```

## Middleware Pipeline

### Managing the Pipeline

```php
// Add multiple middleware at once
$this->getMiddlewarePipeline()->addMultiple([
    new TimingMiddleware(),
    new LoggingMiddleware(),
    new AuthMiddleware(),
]);

// Remove specific middleware
$this->getMiddlewarePipeline()->remove(LoggingMiddleware::class);

// Clear all middleware
$this->clearMiddleware();

// Count middleware
$count = $this->getMiddlewarePipeline()->count();
```

### Direct Pipeline Access

```php
$pipeline = $this->getMiddlewarePipeline();

// Add middleware with priority
$pipeline->add(new CustomMiddleware());

// Execute manually (not recommended)
$exitCode = $pipeline->execute($command, $input, $output);
```

## Advanced Examples

### Rate Limiting Middleware

```php
class RateLimitMiddleware implements MiddlewareInterface
{
    private array $attempts = [];

    public function __construct(
        private int $maxAttempts = 5,
        private int $decayMinutes = 1
    ) {}

    public function handle(Command $command, array $input, Output $output, callable $next): int
    {
        $key = $command->getName();

        if ($this->tooManyAttempts($key)) {
            $output->error('Too many attempts. Please try again later.');
            return 1;
        }

        $this->hit($key);

        return $next($command, $input, $output);
    }

    protected function tooManyAttempts(string $key): bool
    {
        $this->cleanOldAttempts($key);
        return count($this->attempts[$key] ?? []) >= $this->maxAttempts;
    }

    protected function hit(string $key): void
    {
        $this->attempts[$key][] = time();
    }

    protected function cleanOldAttempts(string $key): void
    {
        if (!isset($this->attempts[$key])) {
            return;
        }

        $cutoff = time() - ($this->decayMinutes * 60);
        $this->attempts[$key] = array_filter(
            $this->attempts[$key],
            fn($time) => $time > $cutoff
        );
    }

    public function getPriority(): int
    {
        return 150;
    }
}
```

### Database Transaction Middleware

```php
class TransactionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private $db
    ) {}

    public function handle(Command $command, array $input, Output $output, callable $next): int
    {
        $this->db->beginTransaction();

        try {
            $exitCode = $next($command, $input, $output);

            if ($exitCode === 0) {
                $this->db->commit();
                $output->info('Transaction committed');
            } else {
                $this->db->rollback();
                $output->warning('Transaction rolled back');
            }

            return $exitCode;
        } catch (\Exception $e) {
            $this->db->rollback();
            $output->error('Transaction failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPriority(): int
    {
        return 50;
    }
}
```

### Caching Middleware

```php
class CacheMiddleware implements MiddlewareInterface
{
    public function __construct(
        private $cache,
        private int $ttl = 3600
    ) {}

    public function handle(Command $command, array $input, Output $output, callable $next): int
    {
        $cacheKey = $this->getCacheKey($command, $input);

        if ($cached = $this->cache->get($cacheKey)) {
            $output->info('Using cached result');
            $output->writeln($cached['output']);
            return $cached['exitCode'];
        }

        // Capture output
        ob_start();
        $exitCode = $next($command, $input, $output);
        $capturedOutput = ob_get_clean();

        // Cache result
        $this->cache->set($cacheKey, [
            'output' => $capturedOutput,
            'exitCode' => $exitCode,
        ], $this->ttl);

        return $exitCode;
    }

    protected function getCacheKey(Command $command, array $input): string
    {
        return md5($command->getName() . serialize($input));
    }

    public function getPriority(): int
    {
        return 75;
    }
}
```

## Best Practices

### 1. Single Responsibility

Each middleware should have a single, well-defined responsibility:

```php
// Good - Single responsibility
class AuthenticationMiddleware implements MiddlewareInterface { }
class LoggingMiddleware implements MiddlewareInterface { }

// Bad - Multiple responsibilities
class AuthAndLogMiddleware implements MiddlewareInterface { }
```

### 2. Order Matters

Consider middleware execution order:

```php
$this->middleware(new AuthMiddleware())      // 1. Authenticate first
     ->middleware(new ValidationMiddleware()) // 2. Then validate
     ->middleware(new LoggingMiddleware())    // 3. Log everything
     ->middleware(new TimingMiddleware());    // 4. Time execution
```

### 3. Error Handling

Handle errors gracefully in middleware:

```php
public function handle(Command $command, array $input, Output $output, callable $next): int
{
    try {
        // Middleware logic
        return $next($command, $input, $output);
    } catch (\Exception $e) {
        $output->error('Middleware error: ' . $e->getMessage());
        return 1;
    }
}
```

### 4. Performance

Keep middleware lightweight:

```php
// Good - Fast check
public function handle(Command $command, array $input, Output $output, callable $next): int
{
    if (!$this->shouldRun()) {
        return $next($command, $input, $output);
    }

    // Heavy processing only when needed
    $this->heavyOperation();

    return $next($command, $input, $output);
}
```

## See Also

- [Exit Codes](/guide/exit-codes) - Return appropriate exit codes from middleware
- [Dry Run Mode](/guide/dry-run) - Combine middleware with dry run
- [Signal Handling](/guide/signal-handling) - Handle signals in middleware
