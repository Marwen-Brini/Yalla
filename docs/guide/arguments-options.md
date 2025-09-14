# Arguments and Options

Understanding how to handle command arguments and options is crucial for building flexible CLI applications with Yalla.

## Arguments

Arguments are positional parameters passed to your command. They are processed in the order they are defined.

### Defining Arguments

```php
public function __construct()
{
    $this->name = 'process';
    $this->description = 'Process a file';

    // Required argument
    $this->addArgument('input', 'Input file path', true);

    // Optional argument with default value
    $this->addArgument('output', 'Output file path', false);
}
```

### Accessing Arguments

```php
public function execute(array $input, Output $output): int
{
    // Get required argument
    $inputFile = $this->getArgument($input, 'input');

    // Get optional argument with default value
    $outputFile = $this->getArgument($input, 'output', 'output.txt');

    // Process the files...
    return 0;
}
```

### Usage Examples

```bash
# With required argument only
./cli process input.txt

# With both arguments
./cli process input.txt output.json
```

## Options

Options are named parameters that can be passed in any order using flags.

### Defining Options

```php
public function __construct()
{
    $this->name = 'deploy';
    $this->description = 'Deploy application';

    // Boolean option (flag)
    $this->addOption('force', 'f', 'Force deployment', false);

    // Option with value
    $this->addOption('env', 'e', 'Target environment', 'production');

    // Option without shortcut
    $this->addOption('dry-run', null, 'Simulate deployment', false);
}
```

### Option Types

#### Boolean Options (Flags)

```php
// Definition
$this->addOption('verbose', 'v', 'Enable verbose output', false);

// Usage
./cli deploy --verbose
./cli deploy -v

// In code
$verbose = $this->getOption($input, 'verbose', false);
if ($verbose) {
    $output->info('Verbose mode enabled');
}
```

#### Value Options

```php
// Definition
$this->addOption('timeout', 't', 'Timeout in seconds', 30);

// Usage
./cli deploy --timeout=60
./cli deploy --timeout 60
./cli deploy -t 60

// In code
$timeout = $this->getOption($input, 'timeout', 30);
```

#### Multiple Short Options

```bash
# These are equivalent
./cli deploy -f -v -d
./cli deploy -fvd
```

### Long vs Short Options

```php
// Define both long and short versions
$this->addOption('help', 'h', 'Show help', false);
$this->addOption('version', 'V', 'Show version', false);
$this->addOption('quiet', 'q', 'Suppress output', false);
```

Usage:
```bash
# Long format
./cli deploy --help --version --quiet

# Short format
./cli deploy -h -V -q

# Mixed
./cli deploy --help -Vq
```

## Advanced Patterns

### Variadic Arguments

For commands that accept multiple values:

```php
public function execute(array $input, Output $output): int
{
    // All arguments after the first are treated as files
    $command = $this->getArgument($input, 0);
    $files = array_slice($input['arguments'], 1);

    foreach ($files as $file) {
        $output->info("Processing: $file");
    }

    return 0;
}
```

Usage:
```bash
./cli process file1.txt file2.txt file3.txt
```

### Option Arrays

For options that can be specified multiple times:

```php
public function execute(array $input, Output $output): int
{
    // Parse multiple --tag options
    $tags = [];
    foreach ($input['options'] as $key => $value) {
        if (str_starts_with($key, 'tag')) {
            $tags[] = $value;
        }
    }

    return 0;
}
```

Usage:
```bash
./cli deploy --tag=v1.0 --tag=production --tag=stable
```

### Validation

#### Required Arguments

```php
public function execute(array $input, Output $output): int
{
    $file = $this->getArgument($input, 'file');

    if (empty($file)) {
        $output->error('Error: File argument is required');
        return 1;
    }

    if (!file_exists($file)) {
        $output->error("Error: File '$file' does not exist");
        return 1;
    }

    // Continue processing...
    return 0;
}
```

#### Option Validation

```php
public function execute(array $input, Output $output): int
{
    $format = $this->getOption($input, 'format', 'json');
    $validFormats = ['json', 'xml', 'csv', 'yaml'];

    if (!in_array($format, $validFormats)) {
        $output->error("Invalid format: $format");
        $output->info("Valid formats: " . implode(', ', $validFormats));
        return 1;
    }

    $timeout = $this->getOption($input, 'timeout', 30);
    if (!is_numeric($timeout) || $timeout < 0) {
        $output->error('Timeout must be a positive number');
        return 1;
    }

    // Continue processing...
    return 0;
}
```

## Input Structure

The input array passed to `execute()` has this structure:

```php
[
    'command' => 'deploy',
    'arguments' => [
        'production',
        'v1.2.3'
    ],
    'options' => [
        'force' => true,
        'f' => true,  // Shortcut also included
        'env' => 'staging',
        'e' => 'staging'  // Shortcut also included
    ]
]
```

## Best Practices

### 1. Clear Naming

Use descriptive names for arguments and options:

```php
// Good
$this->addArgument('source-file', 'Path to source file', true);
$this->addOption('output-format', 'o', 'Output format (json|xml|csv)', 'json');

// Poor
$this->addArgument('src', 'Source', true);
$this->addOption('fmt', 'f', 'Format', 'json');
```

### 2. Provide Defaults

Always provide sensible defaults for optional parameters:

```php
$this->addArgument('port', 'Server port', false);

// In execute()
$port = $this->getArgument($input, 'port', 8080);  // Default to 8080
```

### 3. Group Related Options

```php
// Database options
$this->addOption('db-host', null, 'Database host', 'localhost');
$this->addOption('db-port', null, 'Database port', 3306);
$this->addOption('db-name', null, 'Database name', null);
$this->addOption('db-user', null, 'Database user', 'root');
```

### 4. Use Shortcuts Wisely

Reserve single letters for commonly used options:

```php
$this->addOption('verbose', 'v', 'Verbose output', false);
$this->addOption('quiet', 'q', 'Quiet mode', false);
$this->addOption('force', 'f', 'Force operation', false);
$this->addOption('help', 'h', 'Show help', false);
```

### 5. Document Everything

Always provide clear descriptions:

```php
$this->addArgument(
    'config-file',
    'Path to configuration file (JSON or YAML format)',
    true
);

$this->addOption(
    'dry-run',
    null,
    'Run in simulation mode without making actual changes',
    false
);
```

## Complex Example

Here's a complete example showing various argument and option patterns:

```php
<?php

use Yalla\Commands\Command;
use Yalla\Output\Output;

class BackupCommand extends Command
{
    public function __construct()
    {
        $this->name = 'backup';
        $this->description = 'Backup database and files';

        // Arguments
        $this->addArgument('source', 'Source directory or database', true);
        $this->addArgument('destination', 'Backup destination', false);

        // Options
        $this->addOption('type', 't', 'Backup type (full|incremental|differential)', 'full');
        $this->addOption('compress', 'c', 'Compression level (0-9)', 5);
        $this->addOption('encrypt', 'e', 'Encrypt backup', false);
        $this->addOption('exclude', 'x', 'Patterns to exclude', null);
        $this->addOption('dry-run', null, 'Simulate backup', false);
        $this->addOption('verbose', 'v', 'Verbose output', false);
        $this->addOption('quiet', 'q', 'Suppress output', false);
    }

    public function execute(array $input, Output $output): int
    {
        // Get arguments
        $source = $this->getArgument($input, 'source');
        $destination = $this->getArgument($input, 'destination', './backups/' . date('Y-m-d'));

        // Get options
        $type = $this->getOption($input, 'type', 'full');
        $compress = (int) $this->getOption($input, 'compress', 5);
        $encrypt = $this->getOption($input, 'encrypt', false);
        $exclude = $this->getOption($input, 'exclude');
        $dryRun = $this->getOption($input, 'dry-run', false);
        $verbose = $this->getOption($input, 'verbose', false);
        $quiet = $this->getOption($input, 'quiet', false);

        // Validate
        if (!in_array($type, ['full', 'incremental', 'differential'])) {
            $output->error("Invalid backup type: $type");
            return 1;
        }

        if ($compress < 0 || $compress > 9) {
            $output->error('Compression level must be between 0 and 9');
            return 1;
        }

        // Execute backup
        if (!$quiet) {
            $output->info("Starting $type backup...");
            $output->writeln("Source: $source");
            $output->writeln("Destination: $destination");
        }

        if ($verbose) {
            $output->section('Configuration');
            $output->writeln("Type: $type");
            $output->writeln("Compression: $compress");
            $output->writeln("Encryption: " . ($encrypt ? 'Yes' : 'No'));
            $output->writeln("Exclude: " . ($exclude ?: 'None'));
        }

        if ($dryRun) {
            $output->warning('DRY RUN - No actual changes will be made');
        }

        // Perform backup...
        if (!$quiet) {
            $output->success('Backup completed successfully!');
        }

        return 0;
    }
}
```

Usage examples:
```bash
# Simple backup
./cli backup /var/www

# Full featured
./cli backup /var/www /backups/web --type=incremental --compress=9 --encrypt -v

# Dry run with exclusions
./cli backup /home/user --exclude="*.tmp" --exclude="cache/*" --dry-run
```