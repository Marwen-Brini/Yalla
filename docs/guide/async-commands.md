# Async Command Execution

::: tip New in v2.0
Async command execution is a new feature in Yalla CLI v2.0 that allows you to run commands asynchronously with promises and parallel execution support.
:::

## Overview

The `SupportsAsync` trait enables commands to run asynchronously, allowing you to execute long-running tasks without blocking the main thread. This is particularly useful for commands that need to process multiple operations in parallel or handle time-consuming tasks.

## Basic Usage

### Enabling Async Support

Add the `SupportsAsync` trait to your command class:

```php
<?php

use Yalla\Commands\Command;
use Yalla\Commands\Traits\SupportsAsync;
use Yalla\Output\Output;

class ProcessCommand extends Command
{
    use SupportsAsync;

    protected bool $runAsync = true; // Enable async by default
    protected int $asyncTimeout = 300; // 5 minutes timeout

    public function __construct()
    {
        $this->name = 'process';
        $this->description = 'Process data asynchronously';
    }

    public function execute(array $input, Output $output): int
    {
        $output->info('Processing data...');

        // Long running task
        sleep(5);

        $output->success('Processing complete!');

        return 0;
    }
}
```

### Running Commands Asynchronously

Users can run commands asynchronously using the `--async` flag:

```bash
./bin/yalla process --async
```

### Checking Async Execution

```php
public function execute(array $input, Output $output): int
{
    if ($this->shouldRunAsync($input)) {
        $output->info('Running in async mode');
        // Async-specific logic
    }

    // Regular execution
    return 0;
}
```

## Parallel Execution

Run multiple operations in parallel using `runParallel()`:

```php
public function execute(array $input, Output $output): int
{
    $files = ['file1.txt', 'file2.txt', 'file3.txt'];

    $operations = array_map(function($file) {
        return fn() => $this->processFile($file);
    }, $files);

    try {
        $results = $this->runParallel($operations, $output);

        $output->success('All files processed successfully!');

        return 0;
    } catch (\Exception $e) {
        $output->error('Error processing files: ' . $e->getMessage());
        return 1;
    }
}

protected function processFile(string $file): array
{
    // Process the file
    sleep(2); // Simulate processing

    return ['file' => $file, 'status' => 'completed'];
}
```

## Promises

The `executeAsync()` method returns a `Promise` object:

```php
$promise = $command->executeAsync($input, $output);

// Wait for completion
$result = $promise->wait();

// Or use callbacks
$promise
    ->then(function($result) {
        echo "Command completed with exit code: " . $result['exitCode'];
    })
    ->catch(function($error) {
        echo "Command failed: " . $error->getMessage();
    });
```

## Progress Callbacks

Show progress during async execution:

```php
public function executeAsync(array $input, Output $output): Promise
{
    $promise = parent::executeAsync($input, $output);

    if ($output->isVerbose()) {
        $promise->onProgress(function($progress) use ($output) {
            $output->write('.');
        });
    }

    return $promise;
}
```

## Timeout Configuration

Configure command timeout:

```php
class LongRunningCommand extends Command
{
    use SupportsAsync;

    protected int $asyncTimeout = 600; // 10 minutes

    public function execute(array $input, Output $output): int
    {
        // Long running task that may take up to 10 minutes
        $this->processLargeDataset();

        return 0;
    }
}
```

## Error Handling

Handle errors in async commands:

```php
public function execute(array $input, Output $output): int
{
    try {
        $result = $this->runParallel([
            fn() => $this->operation1(),
            fn() => $this->operation2(),
            fn() => $this->operation3(),
        ], $output);

        return 0;
    } catch (\Exception $e) {
        $output->error('Async operation failed: ' . $e->getMessage());

        if ($output->isVerbose()) {
            $output->writeln($e->getTraceAsString());
        }

        return 1;
    }
}
```

## Best Practices

### 1. Use Timeouts Wisely

```php
// Set appropriate timeouts based on your use case
protected int $asyncTimeout = 300; // 5 minutes for normal tasks
// protected int $asyncTimeout = 1800; // 30 minutes for heavy processing
```

### 2. Handle Failures Gracefully

```php
public function execute(array $input, Output $output): int
{
    $operations = [
        fn() => $this->operation1(),
        fn() => $this->operation2(),
    ];

    try {
        $results = $this->runParallel($operations, $output);

        // Check individual results
        foreach ($results as $i => $result) {
            if ($result['error'] ?? false) {
                $output->warning("Operation {$i} failed: {$result['error']}");
            }
        }

        return 0;
    } catch (\Exception $e) {
        return $this->handleException($e, $output);
    }
}
```

### 3. Provide User Feedback

```php
public function execute(array $input, Output $output): int
{
    $output->info('Starting async processing...');

    if ($this->shouldRunAsync($input)) {
        $spinner = $output->createSpinner('Processing in background...');
        $spinner->start();

        // Async work

        $spinner->success('Background processing complete!');
    }

    return 0;
}
```

### 4. Resource Management

```php
public function execute(array $input, Output $output): int
{
    $lockManager = new LockManager();

    if (!$lockManager->acquire('async-process', timeout: 60)) {
        $output->error('Another async process is running');
        return 1;
    }

    try {
        // Async operations
        $this->runParallel($operations, $output);

        return 0;
    } finally {
        $lockManager->release('async-process');
    }
}
```

## Limitations

- Async execution is not true multi-threading; it uses PHP's single-threaded model
- Long-running async commands may still block if not properly managed
- Progress callbacks are limited to the main thread execution
- File descriptors and database connections are shared across async operations

## See Also

- [Signal Handling](/guide/signal-handling) - Handle interrupts in async commands
- [Process Locking](/guide/process-locking) - Prevent concurrent async executions
- [Middleware](/guide/middleware) - Add timing and logging to async commands
