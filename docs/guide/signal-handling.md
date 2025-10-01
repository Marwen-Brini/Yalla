# Signal Handling

::: tip New in v2.0
Signal handling is a new feature in Yalla CLI v2.0 that allows commands to gracefully handle interrupt signals on Unix/Linux systems.
:::

## Overview

The `HandlesSignals` trait provides signal handling capabilities for CLI commands, allowing you to perform cleanup operations when users interrupt your command with Ctrl+C or when the system sends termination signals.

::: warning Platform Support
Signal handling requires the `pcntl` PHP extension and is only available on Unix/Linux systems. On Windows or systems without `pcntl`, signal handlers will be silently ignored.
:::

## Basic Usage

### Registering Signal Handlers

```php
<?php

use Yalla\Commands\Command;
use Yalla\Commands\Traits\HandlesSignals;
use Yalla\Output\Output;

class LongRunningCommand extends Command
{
    use HandlesSignals;

    private bool $interrupted = false;

    public function execute(array $input, Output $output): int
    {
        $this->setSignalOutput($output);

        // Register interrupt handler (Ctrl+C)
        $this->onInterrupt(function() use ($output) {
            $this->interrupted = true;
            $output->warning('\\nInterrupt received, cleaning up...');
            $this->cleanup();
            exit(130); // Standard exit code for SIGINT
        });

        // Process work
        while ($this->hasWork() && !$this->interrupted) {
            $this->dispatchSignals();
            $this->processNextItem();
        }

        return 0;
    }

    protected function cleanup(): void
    {
        // Cleanup logic
    }
}
```

## Signal Types

### SIGINT (Ctrl+C)

Handle user interruption:

```php
$this->onInterrupt(function() use ($output) {
    $output->warning('User interrupted the command');
    $this->performCleanup();
    exit(130);
});
```

### SIGTERM (Termination)

Handle system termination:

```php
$this->onTerminate(function() use ($output) {
    $output->warning('Received termination signal');
    $this->saveState();
    $this->performCleanup();
    exit(143);
});
```

### Custom Signals

Handle any signal:

```php
// Handle SIGHUP (hangup)
$this->onSignal(SIGHUP, function() use ($output) {
    $output->info('Reloading configuration...');
    $this->reloadConfig();
});

// Handle SIGUSR1 (user-defined)
$this->onSignal(SIGUSR1, function() use ($output) {
    $output->info('Received custom signal');
    $this->customAction();
});
```

## Common Signal Handlers

### Register Multiple Handlers

```php
public function execute(array $input, Output $output): int
{
    $this->setSignalOutput($output);

    // Register handlers for common signals
    $this->onCommonSignals(function() use ($output) {
        $output->warning('Shutting down gracefully...');
        $this->cleanup();
        exit(0);
    });

    // Your command logic
    $this->doWork();

    return 0;
}
```

### Default Interrupt Handler

```php
public function execute(array $input, Output $output): int
{
    // Register default handler that shows a message and exits
    $this->registerDefaultInterruptHandler($output);

    // Your command logic
    $this->processData();

    return 0;
}
```

### Graceful Shutdown

```php
public function execute(array $input, Output $output): int
{
    // Register handlers for SIGINT and SIGTERM
    $this->registerGracefulShutdown($output, function() {
        $this->saveProgress();
        $this->closeConnections();
    });

    // Your command logic
    $this->runLongProcess();

    return 0;
}
```

## Advanced Usage

### Dispatching Signals Manually

In long-running loops, dispatch signals manually to check for interrupts:

```php
public function execute(array $input, Output $output): int
{
    $this->onInterrupt(function() {
        $this->interrupted = true;
    });

    while ($this->hasWork()) {
        // Check for signals
        $this->dispatchSignals();

        if ($this->interrupted) {
            $output->warning('Stopping work due to interrupt');
            break;
        }

        $this->processItem();
    }

    return 0;
}
```

### Conditional Signal Handling

```php
public function execute(array $input, Output $output): int
{
    if ($this->isSignalHandlingAvailable()) {
        $this->setupSignalHandlers($output);
        $output->info('Signal handling enabled');
    } else {
        $output->warning('Signal handling not available on this system');
    }

    return 0;
}
```

### Cleanup on Exit

```php
public function execute(array $input, Output $output): int
{
    $this->onInterrupt(function() use ($output) {
        $this->cleanup();
        $output->success('Cleanup completed');
        exit(130);
    });

    try {
        $this->performWork();
    } finally {
        // This runs even if interrupted
        $this->finalCleanup();
    }

    return 0;
}
```

## Signal Management

### Remove Signal Handlers

```php
// Remove a specific signal handler
$this->removeSignalHandler(SIGINT);

// Remove all signal handlers
$this->removeAllSignalHandlers();
```

### Check Handler Registration

```php
if ($this->hasSignalHandler(SIGINT)) {
    $output->info('SIGINT handler is registered');
}

// Get all registered handlers
$handlers = $this->getSignalHandlers();
```

### Enable/Disable Signal Handling

```php
// Check if signals are enabled
if ($this->areSignalsEnabled()) {
    $output->info('Signal handling is active');
}
```

## Best Practices

### 1. Always Cleanup Resources

```php
$this->onInterrupt(function() use ($output, $dbConnection, $fileHandle) {
    $output->info('Cleaning up resources...');

    // Close database connections
    $dbConnection->close();

    // Close file handles
    fclose($fileHandle);

    // Remove temporary files
    $this->removeTempFiles();

    exit(130);
});
```

### 2. Save Progress Before Exiting

```php
$this->onInterrupt(function() use ($output) {
    $output->info('Saving progress...');

    $this->saveState([
        'processed' => $this->processed,
        'remaining' => $this->remaining,
        'timestamp' => time(),
    ]);

    exit(130);
});
```

### 3. Use Appropriate Exit Codes

```php
$this->onInterrupt(function() {
    $this->cleanup();
    exit(130); // 128 + SIGINT (2)
});

$this->onTerminate(function() {
    $this->cleanup();
    exit(143); // 128 + SIGTERM (15)
});
```

### 4. Provide User Feedback

```php
$this->onInterrupt(function() use ($output) {
    $output->writeln(''); // New line after ^C
    $output->warning('⚠ Interrupt signal received');
    $output->info('Performing cleanup...');

    $this->cleanup();

    $output->success('✓ Cleanup completed successfully');
    exit(130);
});
```

### 5. Combine with Process Locking

```php
use Yalla\Process\LockManager;

public function execute(array $input, Output $output): int
{
    $lockManager = new LockManager();

    $this->onInterrupt(function() use ($output, $lockManager) {
        $output->warning('Interrupt received, releasing lock...');
        $lockManager->release('my-command');
        exit(130);
    });

    if (!$lockManager->acquire('my-command')) {
        $output->error('Command is already running');
        return 1;
    }

    try {
        $this->doWork();
    } finally {
        $lockManager->release('my-command');
    }

    return 0;
}
```

## Platform Detection

Check if signal handling is available:

```php
if (!$this->isSignalHandlingAvailable()) {
    $output->warning('Signal handling requires pcntl extension (Unix/Linux only)');
    return 1;
}
```

## Signal Constants

Common signal constants available:

| Signal | Value | Description |
|--------|-------|-------------|
| `SIGINT` | 2 | Interrupt from keyboard (Ctrl+C) |
| `SIGTERM` | 15 | Termination signal |
| `SIGHUP` | 1 | Hangup detected |
| `SIGQUIT` | 3 | Quit from keyboard |
| `SIGUSR1` | 10 | User-defined signal 1 |
| `SIGUSR2` | 12 | User-defined signal 2 |

## See Also

- [Async Commands](/guide/async-commands) - Async commands with signal handling
- [Process Locking](/guide/process-locking) - Prevent concurrent executions
- [Exit Codes](/guide/exit-codes) - Standard exit codes for signals
