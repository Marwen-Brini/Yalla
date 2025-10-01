# Dry Run Mode

::: tip New in v2.0
Dry run mode allows you to preview command operations without actually executing them.
:::

## Overview

The `DryRunnable` trait enables commands to simulate their operations, showing what would happen without making actual changes. This is invaluable for testing, debugging, and building user confidence before running potentially destructive operations.

## Basic Usage

```php
<?php

use Yalla\Commands\Command;
use Yalla\Commands\Traits\DryRunnable;
use Yalla\Output\Output;

class DeployCommand extends Command
{
    use DryRunnable;

    public function __construct()
    {
        parent::__construct();

        $this->name = 'deploy';
        $this->description = 'Deploy application';
        $this->addOption('dry-run', null, 'Preview deployment without executing', false);
    }

    public function execute(array $input, Output $output): int
    {
        $this->setDryRun($this->getOption($input, 'dry-run', false));
        $this->setDryRunOutput($output);

        // Simulate or execute operations
        $this->executeOrSimulate(
            'Upload files to server',
            fn() => $this->uploadFiles(),
            ['files' => 150, 'size' => '45MB']
        );

        $this->executeOrSimulate(
            'Run database migrations',
            fn() => $this->runMigrations(),
            ['migrations' => 5]
        );

        $this->executeOrSimulate(
            'Clear application cache',
            fn() => $this->clearCache()
        );

        if ($this->isDryRun()) {
            $this->showDryRunSummary();
        }

        return 0;
    }
}
```

Running the command:

```bash
# Preview operations
./bin/yalla deploy --dry-run

# Actually execute
./bin/yalla deploy
```

## Dry Run Summary

The summary shows all simulated operations:

```
[DRY RUN] Would execute: Upload files to server
[DRY RUN] Would execute: Run database migrations
[DRY RUN] Would execute: Clear application cache

=== Dry Run Summary ===
Total operations: 3
- Would execute: Upload files to server
- Would execute: Run database migrations
- Would execute: Clear application cache
```

## Verbose Mode

Add context for more detailed previews:

```php
$this->executeOrSimulate(
    'Deploy to production',
    fn() => $this->deploy(),
    [
        'environment' => 'production',
        'server' => 'prod-server-01',
        'branch' => 'main',
        'files' => 245
    ]
);
```

Output in verbose mode:

```
[DRY RUN] Would execute: Deploy to production
  Context:
    - environment: production
    - server: prod-server-01
    - branch: main
    - files: 245
```

## Best Practices

1. **Always add --dry-run option** to commands that modify state
2. **Provide meaningful operation descriptions** for clear previews
3. **Include relevant context** to help users understand the impact
4. **Show the summary** at the end of dry run mode

## See Also

- [Commands](/guide/commands) - Creating commands
- [Output](/guide/output) - Output formatting
