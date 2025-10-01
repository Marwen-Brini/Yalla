# Process Locking

::: tip New in v2.0
Process locking prevents concurrent command executions using file-based locks.
:::

## Overview

The `LockManager` class provides a robust mechanism to prevent multiple instances of a command from running simultaneously. This is essential for cron jobs, background tasks, and any command that shouldn't run concurrently.

## Basic Usage

```php
<?php

use Yalla\Commands\Command;
use Yalla\Process\LockManager;
use Yalla\Output\Output;

class BackupCommand extends Command
{
    public function execute(array $input, Output $output): int
    {
        $lockManager = new LockManager();

        // Try to acquire lock
        if (!$lockManager->acquire('backup-command', timeout: 60)) {
            $output->error('Another backup is already running');
            return 1;
        }

        try {
            $output->info('Starting backup...');
            $this->performBackup();
            $output->success('Backup completed');

            return 0;
        } finally {
            $lockManager->release('backup-command');
        }
    }
}
```

## Lock Methods

### Acquire Lock

```php
// Block until lock is acquired (with timeout)
if ($lockManager->acquire('my-lock', timeout: 30)) {
    // Lock acquired
}

// Try to acquire without blocking
if ($lockManager->tryAcquire('my-lock')) {
    // Lock acquired immediately
}
```

### Release Lock

```php
// Release owned lock
$lockManager->release('my-lock');

// Force release any lock (admin operation)
$lockManager->forceRelease('my-lock');
```

### Check Lock Status

```php
// Check if locked
if ($lockManager->isLocked('my-lock')) {
    echo "Lock is active\n";
}

// Check if stale (abandoned)
if ($lockManager->isStale('my-lock', maxAge: 3600)) {
    $lockManager->clearStale();
}

// Get lock information
$info = $lockManager->getLockInfo('my-lock');
// ['pid' => 1234, 'host' => 'server1', 'timestamp' => 1633024800]
```

## Advanced Usage

### Refresh Lock

Keep lock alive during long operations:

```php
$lockManager->acquire('long-task');

for ($i = 0; $i < 100; $i++) {
    $this->processItem($i);

    // Refresh every 10 items
    if ($i % 10 === 0) {
        $lockManager->refresh('long-task');
    }
}

$lockManager->release('long-task');
```

### Wait for Lock

```php
// Wait up to 60 seconds for lock to be released
if ($lockManager->wait('my-lock', timeout: 60)) {
    // Lock is now available
    $lockManager->acquire('my-lock');
}
```

### List Active Locks

```php
$locks = $lockManager->listLocks();

foreach ($locks as $lock) {
    echo "Lock: $lock\n";
}
```

### Clear Stale Locks

```php
// Remove locks older than 1 hour
$cleared = $lockManager->clearStale(maxAge: 3600);
echo "Cleared $cleared stale locks\n";
```

## Best Practices

1. **Always use try-finally** to ensure locks are released
2. **Set appropriate timeouts** based on expected command duration
3. **Refresh long-running locks** to prevent them from becoming stale
4. **Use descriptive lock names** that indicate the protected resource

## See Also

- [Async Commands](/guide/async-commands) - Async execution with locking
- [Signal Handling](/guide/signal-handling) - Release locks on interrupt
